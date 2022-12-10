<?php

namespace App\Service\User;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ServiceHelper;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializer;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateUserService extends ServiceHelper
{

    // UTILITIES
    protected ValidatorInterface $validator;
    protected SymfonySerializer  $symfonySerializer;
    protected UserPasswordHasherInterface $pwdHasher;


    public function __construct(
        SerializerInterface     $serializer,
        ManagerRegistry         $manager,
        TagAwareCacheInterface  $cachePool,
        UserRepository          $userRepository,
        ValidatorInterface      $validator,
        SymfonySerializer       $symfonySerializer,
        UserPasswordHasherInterface $pwdHasher
    ) {
        parent::__construct($serializer, $manager, $cachePool, $userRepository);

        $this->validator         = $validator;
        $this->symfonySerializer = $symfonySerializer;
        $this->pwdHasher         = $pwdHasher;
    }

    // ============================================================================================
    // ENTRYPOINT
    // ============================================================================================
    public function createUser(?Customer $customer, ?string $request, ?User $authenticatedUser): self
    {
        $this->initHelper();

        // save parameters
        $this->functArgs->set('customer', $customer);
        $this->functArgs->set('request', $request);

        // user is authenticated AND is owned by the customer ?
        if (false === $this->checkAuthenticatedUser($customer, $authenticatedUser)) {
            return $this;
        }

        // check if authenticated user is admin
        if (false === $this->checkUserIsAdmin($authenticatedUser)) {
            $this->errMessages->add(self::ERR_ACCESS_LEVEL);
            $this->httpCode = Response::HTTP_FORBIDDEN;
            return $this;
        }

        // check if the new user is valid
        if (false === $this->checkNewUser()) {
            $this->httpCode = Response::HTTP_BAD_REQUEST;
            return $this;
        }

        // save the new user and add it in cache pool
        if (false === $this->saveUser()) {
            $this->httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            return $this;
        }

        // serialize datas
        $this->serializeDatas(['getUserDetails']);

        $this->httpCode = Response::HTTP_CREATED;
        $this->status = true;
        return $this;
    }

    // ============================================================================================
    // PRIVATE JOBS
    // ============================================================================================
    /**
     * Save the new user and add it in cache pool.
     * On error, return false, otherwise return true.
     */
    protected function saveUser(): bool
    {
        // hash the password !
        $this->functResult->get('user')->setPassword($this->pwdHasher->hashPassword(
            $this->functResult->get('user'), $this->functResult->get('user')->getPassword()
        ));

        // add customer
        $this->functResult->get('user')->setCustomer($this->functArgs->get('customer'));
        // set creation date
        $this->functResult->get('user')->setCreatedAt(new \DateTime());

        try {
            $this->manager->persist($this->functResult->get('user'));
            $this->manager->flush();
        } catch (\Exception $e) {
            $this->errMessages->add(self::ERR_DB_ACCESS);
            return false;
        }

        $idCache = self::CACHE_NAME['getUserDetails'] . "-" . $this->functArgs->get('customer')->getId()
            . "-" . $this->functResult->get('user')->getId();

        // save result in cache pool
        $this->functResult->set('datas', $this->cachePool->get(
            $idCache, 
            function (ItemInterface $item) use ($idCache) {
                $item->tag($idCache);
                return $this->functResult->get('user');
            }
        ));

        // delete user list from cache pool
        $this->cachePool->invalidateTags(
            [self::CACHE_NAME['getUsers'] . "-" . $this->functArgs->get('customer')->getId()]
        );

        return true;
    }

    // ============================================================================================
    // CHECKING JOBS
    // ============================================================================================
    /**
     * Checks if the data provided for the user is valid.
     * If yes, returns true, otherwise returns false.
     */
    protected function checkNewUser(): bool
    {
        if (null === $this->functArgs->get('request')) {
            $this->errMessages->add(self::ERR_NO_DATA);
            return false;
        }
        
        /** @var User $user */
        $user = $this->symfonySerializer->deserialize($this->functArgs->get('request'), User::class, 'json');

        // is used to save 'ROLE_USER' if the role has not been provided
        $user->setRoles($user->getRoles());

        // validate user
        $errors = $this->validator->validate($user);

        if ($errors->count() > 0) {
            // add errors messages
            foreach ($errors as $error) {
                $this->errMessages->add($error->getMessage());
            }

            return false;
        }

        // check roles
        if (!empty($user->getRoles())) {
            /** @var bool $userHasInvalidRole */
            $userHasInvalidRole = false;
            foreach($user->getRoles() as $role) {
                if (!in_array($role, self::USER_ROLES_AVALIABLE)) {
                    $userHasInvalidRole = true;
                    $this->errMessages->add(self::ERR_INVALID_ROLE . $role);
                }
            }

            if (true === $userHasInvalidRole) {
                return false;
            }
        }

        $this->functResult->set('user', $user);

        return true;
    }

}
<?php

namespace App\Service\User;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ServiceHelper;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializer;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateUserService extends ServiceHelper
{

    // UTILITIES
    protected ValidatorInterface $validator;
    protected SymfonySerializer  $symfonySerializer;

    // ERRORS
    protected const ERR_CUSTOMER_NOT_FOUND = "Le client correspondant n'a pas été trouvé.";
    protected const ERR_UNAUTHORIZED       = "Vous n'êtes pas autorisé à faire cette action.";
    protected const ERR_USER_NOT_FOUND     = "L'utilisateur n'a pas été trouvé.";

    // SUCCESS
    protected const OK_UPDATE_SUCCESS = "La mise à jour a été effectuée avec succès.";

    // TOOLS
    protected bool $clearCache;


    public function __construct(
        SerializerInterface     $serializer,
        ManagerRegistry         $manager,
        TagAwareCacheInterface  $cachePool,
        UserRepository          $userRepository,
        ValidatorInterface      $validator,
        SymfonySerializer       $symfonySerializer
    ) {
        parent::__construct($serializer, $manager, $cachePool, $userRepository);

        $this->validator = $validator;
        $this->symfonySerializer = $symfonySerializer;
    }

    // ============================================================================================
    // ENTRYPOINT
    // ============================================================================================
    public function updateUser(?Customer $customer, ?User $user, ?string $request, ?string $token): self
    {
        $this->initHelper();

        // save parameters
        $this->functArgs->set('customer', $customer);
        $this->functArgs->set('user', $user);
        $this->functArgs->set('request', $request);

        // user is authenticated AND is owned by the customer ?
        if (null === $authenticatedUser = $this->checkAuthenticatedUser($customer, $token)) {
            return $this;
        }

        // check if authenticated user is admin
        if (false === $this->checkUserIsAdmin($authenticatedUser)) {
            $this->errMessages->add(self::ERR_ACCESS_LEVEL);
            $this->httpCode = Response::HTTP_FORBIDDEN;
            return $this;
        }

        // check if the user to update is valid and owned by the same customer
        if (false === $this->checkUserIsOwnedByCustomer($user, $customer)) {
            return $this;
        }

        // check if the data provided is valid
        if (false === $this->checkNewUserDatas()) {
            $this->httpCode = Response::HTTP_BAD_REQUEST;
            return $this;
        }

        // save new datas and add it in cache pool
        if (false === $this->saveUser()) {
            $this->httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            return $this;
        }

        // serialized datas
        $this->serializeMessage(self::OK_UPDATE_SUCCESS);

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
        // add updated date
        $this->functResult->get('user')->setUpdatedAt(new \DateTime());

        try {
            $this->manager->persist($this->functResult->get('user'));
            $this->manager->flush();
        } catch (\Exception $e) {
            $this->errMessages->add(self::ERR_DB_ACCESS);
            return false;
        }

        $idCache = self::CACHE_NAME['getUserDetails'] . "-" . $this->functArgs->get('customer')->getId()
            . "-" . $this->functResult->get('user')->getId();

        // clear cache
        $this->cachePool->invalidateTags([$idCache]);

        if (true === $this->clearCache) {
            $this->cachePool->invalidateTags(
                [self::CACHE_NAME['getUsers'] . "-" . $this->functArgs->get('customer')->getId()]
            );
        }

        // save result in cache pool
        $this->functResult->set('datas', $this->cachePool->get(
            $idCache, 
            function (ItemInterface $item) use ($idCache) {
                $item->tag($idCache);
                return $this->functResult->get('user');
            }
        ));

        return true;
    }

    // ============================================================================================
    // CHECKING JOBS
    // ============================================================================================
    /**
     * Checks if the new data provided for the user is valid.
     * If yes, returns true, otherwise returns false.
     */
    protected function checkNewUserDatas(): bool
    {
        //** @var User $newUserDatas */
        /*
        $newUserDatas = $this->serializer->deserialize(
            $this->functArgs->get('request'),
            User::class,
            'json'
        );

        $this->functResult->set('user', $this->functArgs->get('user'));

        // update user (only email, username and roles)
        if (!empty($newUserDatas->getUsername())) {
            $this->functResult->get('user')->setUsername($newUserDatas->getUsername());
            $this->clearCache = true;
        }

        if (!empty($newUserDatas->getEmail())) {
            $this->functResult->get('user')->setEmail($newUserDatas->getEmail());
        }

        if (!empty($newUserDatas->getRoles())) {
            $this->functResult->get('user')->setRoles($newUserDatas->getRoles());
        }
        */

        if (null === $this->functArgs->get('request')) {
            $this->errMessages->add(self::ERR_NO_DATA);
            return false;
        }

        $this->functResult->set('user', $this->symfonySerializer->deserialize(
            $this->functArgs->get('request'), 
            User::class, 
            'json', 
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $this->functArgs->get('user'),
                'attributes' => ['username', 'email', 'roles']
            ]
        ));

        // validate user
        $errors = $this->validator->validate($this->functResult->get('user'));

        if ($errors->count() > 0) {
            // add errors messages
            foreach ($errors as $error) {
                $this->errMessages->add($error->getMessage());
            }

            return false;
        }

        // check roles
        if (empty($this->functResult->get('user')->getRoles())) {
            // add 'ROLE_USER' by default
            $this->functResult->get('user')->setRoles([self::USER_ROLES_AVALIABLE[0]]);
        }
        else {
            /** @var bool $userHasInvalidRole */
            $userHasInvalidRole = false;
            foreach($this->functResult->get('user')->getRoles() as $role) {
                if (!in_array($role, self::USER_ROLES_AVALIABLE)) {
                    $userHasInvalidRole = true;
                    $this->errMessages->add(self::ERR_INVALID_ROLE . $role);
                }
            }

            if (true === $userHasInvalidRole) {
                return false;
            }
        }

        // clear cache pool if the username has been modified
        if ($this->functArgs->get('user')->getUsername() !== $this->functArgs->get('user')->getUsername()) {
            $this->clearCache = true;
        }
        
        return true;
    }

    // ============================================================================================
    // HELPER
    // ============================================================================================
    protected function initHelper(): void
    {
        parent::initHelper();

        $this->clearCache = false;
    }

}
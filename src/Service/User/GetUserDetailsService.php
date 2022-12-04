<?php

namespace App\Service\User;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ServiceHelper;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetUserDetailsService extends ServiceHelper
{

    // ERRORS
    protected const ERR_USER_NOT_FOUND = "L'utilisateur n'a pas été trouvé.";

    public function __construct(
        SerializerInterface     $serializer,
        ManagerRegistry         $manager,
        TagAwareCacheInterface  $cachePool,
        UserRepository          $userRepository
    ) {
        parent::__construct($serializer, $manager, $cachePool, $userRepository);
    }

    // ============================================================================================
    // ENTRYPOINT
    // ============================================================================================
    public function getUser(?Customer $customer, ?User $user, ?string $token): self
    {
        $this->initHelper();

        // save parameters
        $this->functArgs->set('customer', $customer);
        $this->functArgs->set('user', $user);

        // user is authenticated AND is owned by the customer ?
        if (null === $this->checkAuthenticatedUser($customer, $token)) {
            return $this;
        }

        // the user is valid ?
        if (false === $this->checkUser()) {
            $this->httpCode = Response::HTTP_NOT_FOUND;
            return $this;
        }

        // the user to be consulted is owned by the same client ?
        if (false === $this->checkUserIsOwnedByCustomer($user, $customer)) {
            $this->httpCode = Response::HTTP_FORBIDDEN;
            return $this;
        }

        // save result and add in cache pool
        $this->saveInCache();

        // serialize datas
        $this->serializeDatas(['getUserDetails']);

        $this->status = true;
        return $this;
    }

    // ============================================================================================
    // INTERNAL JOBS
    // ============================================================================================
    /**
     * Saves the user's details in the cache.
     */
    protected function saveInCache(): void
    {
        $idCache = self::CACHE_NAME['getUserDetails'] . "-" . $this->functArgs->get('customer')->getId()
            . "-" . $this->functArgs->get('user')->getId();

        // save result in cache pool
        $this->functResult->set('datas', $this->cachePool->get(
            $idCache, 
            function (ItemInterface $item) use ($idCache) {
                $item->tag($idCache);
                return $this->functArgs->get('user');
            }
        ));
    }

    // ============================================================================================
    // CHECKING JOBS
    // ============================================================================================
    /**
     * Check if the user is valid.
     * If yes returns true, otherwise returns false.
     */
    protected function checkUser(): bool
    {
        if (null === $this->functArgs->get('user')) {
            $this->errMessages->add(self::ERR_USER_NOT_FOUND);
            return false;
        }

        return true;
    }

}
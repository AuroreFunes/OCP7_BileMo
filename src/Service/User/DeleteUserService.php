<?php

namespace App\Service\User;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ServiceHelper;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DeleteUserService extends ServiceHelper
{

    // ERRORS
    protected const ERR_CUSTOMER_NOT_FOUND = "Le client correspondant n'a pas été trouvé.";
    protected const ERR_USER_NOT_FOUND     = "L'utilisateur n'a pas été trouvé.";
    protected const ERR_UNAUTHORIZED       = "Vous n'êtes pas autorisé à faire cette action.";

    // SUCCESS
    protected const OK_DELETE_SUCCESS = "La suppression a été effectuée avec succès.";


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
    public function deleteUser(?Customer $customer, ?User $user, ?User $authenticatedUser): self
    {
        $this->initHelper();

        // save parameters
        $this->functArgs->set('customer', $customer);
        $this->functArgs->set('user', $user);

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

        // check if the user to delete is valid and owned by the same customer
        if (false === $this->checkUserIsOwnedByCustomer($user, $customer)) {
            return $this;
        }

        // delete the user and clear cache
        if (false === $this->makeDelete()) {
            $this->httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            return $this;
        }

        // serialized datas
        $this->serializeMessage(["info" => self::OK_DELETE_SUCCESS]);

        $this->httpCode = Response::HTTP_NO_CONTENT;
        $this->status = true;
        return $this;
    }

    // ============================================================================================
    // PRIVATE JOBS
    // ============================================================================================
    /**
     * Delete the user.
     * On error return false, otherwise return true.
     */
    protected function makeDelete(): bool
    {
        // save cache name (user details)
        $cacheTagUserDetails = self::CACHE_NAME['getUserDetails'] . "-" . $this->functArgs->get('customer')->getId()
            . "-" . $this->functArgs->get('user')->getId();

        try {
            $this->manager->remove($this->functArgs->get('user'));
            $this->manager->flush();
        } catch (\Exception $e) {
            $this->errMessages->add(self::ERR_DB_ACCESS);
            return false;
        }

        // save cache name (users list)
        $cacheTagUsersList = self::CACHE_NAME['getUsers'] . "-" . $this->functArgs->get('customer')->getId();

        // delete user details ans users list from cache pool
        $this->cachePool->invalidateTags([$cacheTagUserDetails, $cacheTagUsersList]);

        return true;
    }

}
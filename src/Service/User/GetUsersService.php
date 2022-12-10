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


class GetUsersService extends ServiceHelper
{

    // ERRORS
    protected const ERR_NO_DATA = "Il n'y a pas d'utilisateur dans la page choisie.";


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
    public function getUsers(?Customer $customer, ?User $authenticatedUser, int $page = 1): self
    {
        $this->initHelper();

        // save parameters
        $this->functArgs->set('customer', $customer);
        $this->functArgs->set('page', $page);

        // user is authenticated AND is owned by the customer ?
        if (false === $this->checkAuthenticatedUser($customer, $authenticatedUser)) {
            return $this;
        }

        // check if the page number is valid
        if (false === $this->checkPageNumber()) {
            $this->httpCode = Response::HTTP_BAD_REQUEST;
            return $this;
        }

        // find users for the customer provides (with page number)
        if (false === $this->findUsers()) {
            $this->httpCode = Response::HTTP_NO_CONTENT;
            return $this;
        }

        // serialize datas
        $this->serializeDatas(['getUsers']);

        $this->status = true;
        return $this;
    }

    // ============================================================================================
    // PRIVATE JOBS
    // ============================================================================================
    /**
     * Search for users of this client with the provided page number and put the answer in the cache pool.
     * Returns false if there is no data, otherwise returns true.
     */
    protected function findUsers(): bool
    {
        $idCache = self::CACHE_NAME['getUsers'] . "-" . $this->functArgs->get('customer')->getId()
            . "-" . $this->functArgs->get('page');
        
        $users = $this->cachePool->get(
            $idCache, 
            function (ItemInterface $item) {
                $item->tag(self::CACHE_NAME['getUsers'] . "-" . $this->functArgs->get('customer')->getId());
                return $this->userRepository->findAllInPage(
                    $this->functArgs->get('customer'),
                    $this->functArgs->get('page') 
                );
            }
        );

        if (empty($users)) {
            $this->errMessages->add(self::ERR_NO_DATA);
            return false;
        }

        // save result
        $this->functResult->set('datas', $users);

        return true;
    }

}
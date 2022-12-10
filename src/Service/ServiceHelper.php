<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

abstract class ServiceHelper
{

    // PARAMETERS
    protected const USER_ROLES_AVALIABLE = ['ROLE_USER', 'ROLE_ADMIN'];
    protected const ROLE_ADMIN = self::USER_ROLES_AVALIABLE[1];

    protected const CACHE_NAME = [
        'getUsers'          => 'getAllUsers',
        'getUserDetails'    => 'getUserDetails',
        'getPhones'         => 'getAllPhones',
        'getPhoneDetails'   => 'getPhoneDetails'
    ];

    // DEPENDENCIES
    protected ObjectManager $manager;
    protected TagAwareCacheInterface $cachePool;
    protected UserRepository $userRepository;

    // UTILITIES
    protected bool $status;
    protected int  $httpCode;
    protected ArrayCollection $functArgs;
    protected ArrayCollection $functResult;
    protected ArrayCollection $errMessages;

    // ERRORS
    protected const ERR_DB_ACCESS        = "Une erreur interne s'est produite.";
    protected const ERR_INVALID_USER     = "L'utilisateur n'a pas été trouvé.";
    protected const ERR_AUTHENTICATION   = "Seul un utilisateur authentifié peut accéder à l'API.";
    protected const ERR_INVALID_CUSTOMER = "Le client n'a pas été trouvé.";
    protected const ERR_ACCESS_LEVEL     = "Vous n'avez pas un niveau d'accès suffisant.";
    protected const ERR_UNAUTHORIZED     = "Vous ne pouvez pas effectuer cette opération confidentielle.";
    protected const ERR_INVALID_ROLE     = "Ce rôle n'est pas pris en charge : ";
    protected const ERR_NO_DATA          = "Les données doivent être fournies.";
    protected const ERR_INVALID_PAGE_NUMBER = "Le numéro de page n'est pas valide.";


    public function __construct(
        SerializerInterface     $serializer,
        ManagerRegistry         $manager,
        TagAwareCacheInterface  $cachePool,
        UserRepository          $userRepository
    ) {
        $this->serializer       = $serializer;
        $this->manager          = $manager->getManager();
        $this->cachePool        = $cachePool;
        $this->userRepository   = $userRepository;
    }


    // ============================================================================================
    // HELPER
    // ============================================================================================
    protected function initHelper(): void
    {
        $this->status       = false;
        $this->httpCode     = Response::HTTP_OK;
        $this->functArgs    = new ArrayCollection();
        $this->functResult  = new ArrayCollection();
        $this->errMessages  = new ArrayCollection();
    }


    // ============================================================================================
    // SERIALIZE
    // ============================================================================================

    protected function serializeDatas(array $groups): void
    {
        $this->functResult->set('serializedDatas', $this->serializer->serialize(
            $this->functResult->get('datas'),
            'json',
            SerializationContext::create()->setGroups($groups)
        ));
    }

    protected function serializeMessage(array $messages): void
    {
        $this->functResult->set('serializedDatas', json_encode($messages));
    }


    // ============================================================================================
    // CHECK USER
    // ============================================================================================

    /**
     * Check if the user is authenticated AND is owned by the customer.
     * If yes, return true.
     * Returns false :
     *      - if the user is not authenticated, 
     *      - if the customer is not valid, 
     *      - or if the user is not owned by this customer
     */
    protected function checkAuthenticatedUser(?Customer $customer, ?User $authenticatedUser): bool
    {
        // first, check if the user is authenticated
        if (false === $this->checkUser($authenticatedUser)) {
            $this->errMessages->add(self::ERR_AUTHENTICATION);
            $this->httpCode = Response::HTTP_UNAUTHORIZED;
            return false;
        }

        // then check if the customer is valid
        if (false === $this->checkCustomer($customer)) {
            $this->httpCode = Response::HTTP_NOT_FOUND;
            return false;
        }

        // finally, check if the authenticated user is owned by the customer
        if (false === $this->checkUserIsOwnedByCustomer($authenticatedUser, $customer)) {
            $this->httpCode = Response::HTTP_FORBIDDEN;
            return false;
        }

        return true;
    }

    /**
     * Check the customer.
     * Returns true if it exists, otherwise returns false.
     * Note : we could add other verifications in the future.
     */
    protected function checkUser(?User $user): bool
    {
        if (null === $user) {
            return false;
        }

        return true;
    }

    /**
     * Check the customer.
     * Returns true if it exists, otherwise returns false.
     * Note : we could add other verifications in the future.
     */
    protected function checkCustomer(?Customer $customer): bool
    {
        if (null === $customer) {
            $this->errMessages->add(self::ERR_INVALID_CUSTOMER);
            return false;
        }

        return true;
    }

    /**
     * Checks if the user is owned by the client.
     * If yes return true, otherwise return false.
     */
    protected function checkUserIsOwnedByCustomer(?User $user, Customer $customer): bool
    {
        if (null === $user) {
            $this->errMessages->add(self::ERR_INVALID_USER);
            $this->httpCode = Response::HTTP_NOT_FOUND;
            return false;
        }

        if ($user->getCustomer()->getId() !== $customer->getId()) {
            $this->errMessages->add(self::ERR_UNAUTHORIZED);
            $this->httpCode = Response::HTTP_FORBIDDEN;
            return false;
        }

        return true;
    }

    /**
     * Returns true if the user has the admin role.
     * Returns false otherwise.
     */
    protected function checkUserIsAdmin(User $user): bool
    {
        return in_array(self::ROLE_ADMIN, $user->getRoles());
    }


    // ============================================================================================
    // CHECK PARAMETERS
    // ============================================================================================
    /**
     * Returns true if the page number is an integer greater than 1, otherwise returns false.
     */
    protected function checkPageNumber(): bool
    {
        if (false === filter_var($this->functArgs->get('page'), FILTER_VALIDATE_INT, 
            ['options' => ['min_range' => 1]])
        ) {
            $this->errMessages->add(self::ERR_INVALID_PAGE_NUMBER);
            return false;
        }

        return true;
    }


    // ============================================================================================
    // OUT
    // ============================================================================================
    public function getStatus()
    {
        return $this->status;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getArguments()
    {
        return $this->functArgs;
    }

    public function getDatas()
    {
        return $this->functResult->get('serializedDatas');
    }

    public function getUnserializedDatas()
    {
        return $this->functResult->get('datas');
    }

    public function getErrors()
    {
        return $this->serializer->serialize($this->errMessages, 'json');
    }

    public function getUnserializedErrors()
    {
        return $this->errMessages;
    }

    public function getJsonResponse(array $headers = []): JsonResponse
    {
        if (false === $this->status) {
            return new JsonResponse($this->getErrors(), $this->httpCode, $headers, true);
        }

        return new JsonResponse($this->getDatas(), $this->httpCode, $headers, true);
    }

}
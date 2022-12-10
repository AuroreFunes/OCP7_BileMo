<?php

namespace App\Service\Phone;

use App\Entity\Phone;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ServiceHelper;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;


class GetPhoneDetailsService extends ServiceHelper
{
    // ERRORS
    protected const ERR_NOT_FOUND = "Aucun modèle n'a été trouvé.";


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
    public function getPhone(?Phone $phone, ?User $authenticatedUser): self
    {
        $this->initHelper();

        // save parameters
        $this->functArgs->set('phone', $phone);

        // check if user is authenticated
        if (false === $this->checkUser($authenticatedUser)) {
            $this->httpCode = Response::HTTP_UNAUTHORIZED;
            return $this;
        }

        // get phone
        if (false === $this->checkPhone()) {
            $this->httpCode = Response::HTTP_NOT_FOUND;
            return $this;
        }

        // save result and add in cache pool
        $this->saveInCache();

        // serialize datas
        $this->serializeDatas(['getPhoneDetails']);

        $this->status = true;
        return $this;
    }

    // ============================================================================================
    // INTERNAL JOBS
    // ============================================================================================
    /**
     * Add result in cache pool
     */
    protected function saveInCache(): void
    {
        $idCache = self::CACHE_NAME['getPhoneDetails'] . "-" . $this->functArgs->get('phone')->getId();
        
        // save result in cache pool
        $this->functResult->set('datas', $this->cachePool->get(
            $idCache, 
            function (ItemInterface $item) use ($idCache) {
                $item->tag($idCache);
                // Symfony lazy loading : accessing the phone brand and not making an additional request to retrieve an object we already have
                $tmp = $this->functArgs->get('phone')->getBrand()->getName();
                return $this->functArgs->get('phone');
            }
        ));
    }

    // ============================================================================================
    // CHECKING JOBS
    // ============================================================================================
    /**
     * Check if the phone exists.
     * If yes returns true, otherwise returns false.
     */
    protected function checkPhone(): bool
    {
        if (null === $this->functArgs->get('phone')) {
            $this->errMessages->add(self::ERR_NOT_FOUND);
            return false;
        }

        return true;
    }

}
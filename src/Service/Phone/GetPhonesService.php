<?php

namespace App\Service\Phone;

use App\Entity\User;
use App\Repository\PhoneRepository;
use App\Repository\UserRepository;
use App\Service\ServiceHelper;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetPhonesService extends ServiceHelper
{

    // ERRORS
    protected const ERR_NO_DATA = "Aucune donnée n'est disponible pour le moment.";

    // UTILITIES
    protected PhoneRepository $phoneRepository;


    public function __construct(
        SerializerInterface     $serializer,
        ManagerRegistry         $manager,
        TagAwareCacheInterface  $cachePool,
        UserRepository          $userRepository,
        PhoneRepository         $phoneRepository
    ) {
        parent::__construct($serializer, $manager, $cachePool, $userRepository);

        $this->phoneRepository  = $phoneRepository;
    }

    // ============================================================================================
    // ENTRYPOINT
    // ============================================================================================
    public function getAllPhones(int $page = 1, ?User $authenticatedUser): self
    {
        $this->initHelper();

        $this->functArgs->set('page', $page);

        // check if user is authenticated
        if (false === $this->checkUser($authenticatedUser)) {
            $this->errMessages->add(self::ERR_AUTHENTICATION);
            $this->httpCode = Response::HTTP_UNAUTHORIZED;
            return $this;
        }

        // check if the page number is valid
        if (false === $this->checkPageNumber()) {
            $this->httpCode = Response::HTTP_BAD_REQUEST;
            return $this;
        }

        // get phones (with page number)
        if (false === $this->findPhones()) {
            $this->httpCode = Response::HTTP_NO_CONTENT;
            return $this;
        }

        // serialize datas
        $this->serializeDatas(['getPhones']);

        $this->status = true;
        return $this;
    }

    // ============================================================================================
    // PRIVATE JOBS
    // ============================================================================================
    protected function findPhones(): bool
    {
        $idCache = self::CACHE_NAME['getPhones'] . "-" . $this->functArgs->get('page');
        
        $phones = $this->cachePool->get(
            $idCache, 
            function (ItemInterface $item) {
                $item->tag(self::CACHE_NAME['getPhones']);
                return $this->phoneRepository->findAllInPage($this->functArgs->get('page'));
            }
        );

        if (empty($phones)) {
            $this->errMessages->add(self::ERR_NO_DATA);
            return false;
        }

        // save result
        $this->functResult->set('datas', $phones);

        return true;
    }

}
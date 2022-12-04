<?php

namespace App\Test;

use App\Entity\Phone;
use App\Service\Phone\GetPhoneDetailsService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class GetPhoneDetailsServiceTest extends KernelTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;

    private GetPhoneDetailsService $service;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->service = $container->get(GetPhoneDetailsService::class);
    }

    public function testGetPhoneDetailsOk()
    {
        // init before run
        /** @var Phone $phone */
        $phone = $this->entityManager->getRepository(Phone::class)->findAll()[0];

        // run service
        $this->service->getPhone($phone, 'token1');

        // check status
        $this->assertTrue($this->service->getStatus());

        // count errors
        $this->assertEmpty($this->service->getUnserializedErrors());

        // read result
        /** @var Phone $phoneResult */
        $phoneResult = $this->service->getUnserializedDatas();
        $this->assertEquals($phone->getId(), $phoneResult->getId());
        $this->assertEquals($phone->getName(), $phoneResult->getName());
        $this->assertEquals($phone->getColor(), $phoneResult->getColor());
        $this->assertEquals($phone->getSellingPrice(), $phoneResult->getSellingPrice());

        // http code
        $this->assertEquals(Response::HTTP_OK, $this->service->getHttpCode());
    }

    public function testGetPhoneNotFound()
    {
        // run service
        $this->service->getPhone(null, 'token1');

        // check status
        $this->assertFalse($this->service->getStatus());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Aucun modèle n'a été trouvé.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->service->getHttpCode());
    }

// cases where the token is invalid, expired, or missing are already tested in "GetPhonesServiceTest"

}
<?php

namespace App\Test;

use App\Entity\Phone;
use App\Service\Phone\GetPhonesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class GetPhonesServiceTest extends KernelTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;
    private ParameterBagInterface $params;

    private GetPhonesService $service;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->params = $container->get(ParameterBagInterface::class);

        $this->service = $container->get(GetPhonesService::class);
    }

    public function testGetPhonesOk()
    {
        // tests before run
        $phonesNbInMyPage = count($this->entityManager->getRepository(Phone::class)->findAllInPage(1));

        // run service
        $this->service->getAllPhones(1, 'token1');

        // check status
        $this->assertTrue($this->service->getStatus());

        // count result
        $this->assertCount($phonesNbInMyPage, $this->service->getUnserializedDatas());

        // count errors
        $this->assertEmpty($this->service->getUnserializedErrors());

        // http code
        $this->assertEquals(Response::HTTP_OK, $this->service->getHttpCode());
    }

    public function testWithNoDataInPage()
    {
        // tests before run
        $phonesNbInMyPage = count($this->entityManager->getRepository(Phone::class)->findAll());
        $maxPage = $phonesNbInMyPage / $this->params->get('phones_per_page');

        // run service
        $this->service->getAllPhones($maxPage + 1, 'token1');

        // check status
        $this->assertFalse($this->service->getStatus());

        // count result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Aucune donnée n'est disponible pour le moment.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->service->getHttpCode());
    }

    public function testWithInvalidPageNumber()
    {
        // run service
        $this->service->getAllPhones(-1, 'token1');

        // check status
        $this->assertFalse($this->service->getStatus());

        // count result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Le numéro de page n'est pas valide.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_BAD_REQUEST , $this->service->getHttpCode());
    }

    public function testWithExpiredToken()
    {
        // run service
        $this->service->getAllPhones(1, 'expiredToken!');

        // check status
        $this->assertFalse($this->service->getStatus());

        // count result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Le jeton a expiré.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_UNAUTHORIZED , $this->service->getHttpCode());
    }

    public function testWithWrongToken()
    {
        // run service
        $this->service->getAllPhones(1, 'wrongtoken!');

        // check status
        $this->assertFalse($this->service->getStatus());

        // count result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Le jeton n'est pas valide.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_UNAUTHORIZED , $this->service->getHttpCode());
    }

    public function testWithoutToken()
    {
        // run service
        $this->service->getAllPhones(1, null);

        // check status
        $this->assertFalse($this->service->getStatus());

        // count result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Le jeton doit être fourni.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_UNAUTHORIZED , $this->service->getHttpCode());
    }

}

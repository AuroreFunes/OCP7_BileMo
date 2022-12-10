<?php

namespace App\Test;

use App\Entity\Phone;
use App\Entity\User;
use App\Service\Phone\GetPhonesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class GetPhonesServiceTest extends KernelTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;
    private ParameterBagInterface $params;
    private User $user;

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

        $this->user = $this->entityManager->getRepository(User::class)->findAll()[0];
    }

    public function testGetPhonesOk()
    {
        // tests before run
        $phonesNbInMyPage = count($this->entityManager->getRepository(Phone::class)->findAllInPage(1));

        // run service
        $this->service->getAllPhones(1, $this->user);

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
        $this->service->getAllPhones($maxPage + 1, $this->user);

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
        $this->service->getAllPhones(-1, $this->user);

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

    public function testWithUnauthenticatedUser()
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
        $this->assertEquals("Seul un utilisateur authentifié peut accéder à l'API.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_UNAUTHORIZED , $this->service->getHttpCode());
    }

}

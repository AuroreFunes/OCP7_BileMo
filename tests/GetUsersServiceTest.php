<?php

namespace App\Test;

use App\Entity\Customer;
use App\Entity\User;
use App\Service\User\GetUsersService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class GetUsersServiceTest extends KernelTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;
    private ParameterBagInterface $params;

    // utilities
    private Customer $customer;
    private Customer $customer2;
    private User $authenticatedUser;


    private GetUsersService $service;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->params = $container->get(ParameterBagInterface::class);

        $this->service = $container->get(GetUsersService::class);

        // init customers and users
        $customers = $this->entityManager->getRepository(Customer::class)->findAll();
        $this->customer  = $customers[0];
        $this->customer2 = $customers[1];
        $this->authenticatedUser = $this->customer->getUsers()[0];
    }

    public function testGetUsersOk()
    {
        // inits before run
        $usersNbInMyPage = count($this->entityManager->getRepository(User::class)->findAllInPage($this->customer, 1));

        // run service
        $this->service->getUsers($this->customer, $this->authenticatedUser);

        // check status
        $this->assertTrue($this->service->getStatus());

        // count result
        $this->assertCount($usersNbInMyPage, $this->service->getUnserializedDatas());

        // count errors
        $this->assertEmpty($this->service->getUnserializedErrors());

        // http code
        $this->assertEquals(Response::HTTP_OK, $this->service->getHttpCode());
    }

    public function testWithNoDataInPage()
    {
        // inits before run
        $maxPage = round(count($this->customer->getUsers()) / $this->params->get('users_per_page'), 1);

        // run service
        $this->service->getUsers($this->customer, $this->authenticatedUser, $maxPage + 2);

        // check status
        $this->assertFalse($this->service->getStatus());

        // count result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Il n'y a pas d'utilisateur dans la page choisie.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->service->getHttpCode());
    }

    public function testWithInvalidPageNumber()
    {
        // run service
        $this->service->getUsers($this->customer, $this->authenticatedUser, -1);

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

    public function testUserIsOwnedByWrongCustomer()
    {
        // inits before run
        /** @var User $userInCustomer2 */
        $userInCustomer2 = $this->customer2->getUsers()[0];

        // run service
        $this->service->getUsers($this->customer, $userInCustomer2);

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Vous ne pouvez pas effectuer cette opération confidentielle.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->service->getHttpCode());
    }

// cases where the user is not authenticated is already tested in "GetPhonesServiceTest"
}
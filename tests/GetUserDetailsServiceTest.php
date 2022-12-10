<?php

namespace App\Test;

use App\Entity\Customer;
use App\Entity\User;
use App\Service\User\GetUserDetailsService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class GetUserDetailsServiceTest extends KernelTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;

    // utilities
    private Customer $customer;
    private Customer $customer2;
    private User $authenticatedUser;
    private User $userToRead;

    private GetUserDetailsService $service;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->service = $container->get(GetUserDetailsService::class);

        // init customers and users
        $customers = $this->entityManager->getRepository(Customer::class)->findAll();
        $this->customer  = $customers[0];
        $this->customer2 = $customers[1];
        $users = $this->customer->getUsers();
        $this->authenticatedUser = $users[0];
        $this->userToRead = $users[1];
    }

    public function testGetUserDetailsOk()
    {
        // run service
        $this->service->getUser($this->customer, $this->userToRead, $this->authenticatedUser);

        // check status
        $this->assertTrue($this->service->getStatus());

        // count errors
        $this->assertEmpty($this->service->getUnserializedErrors());

        // read result
        /** @var User $userResult */
        $userResult = $this->service->getUnserializedDatas();
        $this->assertEquals($this->userToRead->getId(), $userResult->getId());
        $this->assertEquals($this->userToRead->getUsername(), $userResult->getUsername());
        $this->assertEquals($this->userToRead->getEmail(), $userResult->getEmail());

        // http code
        $this->assertEquals(Response::HTTP_OK, $this->service->getHttpCode());
    }

    public function testUserNotFound()
    {
        // run service
        $this->service->getUser($this->customer, null, $this->authenticatedUser);

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("L'utilisateur n'a pas été trouvé.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->service->getHttpCode());
    }

    public function testCustomerNotFound()
    {
        // run service
        $this->service->getUser(null, $this->userToRead, $this->authenticatedUser);

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Le client n'a pas été trouvé.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->service->getHttpCode());
    }

    public function testUserIsOwnedByWrongCustomer()
    {
        // run service
        $this->service->getUser($this->customer, $this->customer2->getUsers()[0], $this->authenticatedUser);

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
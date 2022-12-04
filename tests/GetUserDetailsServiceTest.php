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

    private GetUserDetailsService $service;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->service = $container->get(GetUserDetailsService::class);
    }

    public function testGetUserDetailsOk()
    {
        // inits before run
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->findAll()[0];
        /** @var User $user */
        $user = $customer->getUsers()[0];
        /** @var User $userToRead */
        $userToRead = $customer->getUsers()[1];

        // run service
        $this->service->getUser($customer, $userToRead, $user->getToken());

        // check status
        $this->assertTrue($this->service->getStatus());

        // count errors
        $this->assertEmpty($this->service->getUnserializedErrors());

        // read result
        /** @var User $userResult */
        $userResult = $this->service->getUnserializedDatas();
        $this->assertEquals($userToRead->getId(), $userResult->getId());
        $this->assertEquals($userToRead->getUsername(), $userResult->getUsername());
        $this->assertEquals($userToRead->getEmail(), $userResult->getEmail());

        // http code
        $this->assertEquals(Response::HTTP_OK, $this->service->getHttpCode());
    }

    public function testUserNotFound()
    {
        // inits before run
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->findAll()[0];
        /** @var User $user */
        $user = $customer->getUsers()[0];

        // run service
        $this->service->getUser($customer, null, $user->getToken());

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
        // inits before run
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->findAll()[0];
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findAll()[0];

        // run service
        $this->service->getUser(null, $customer->getUsers()[1], $user->getToken());

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
        // inits before run
        /** @var Customer $customer */
        $customer1 = $this->entityManager->getRepository(Customer::class)->findAll()[0];
        /** @var User $user */
        $user = $customer1->getUsers()[0];
        /** @var Customer $customer */
        $customer2 = $this->entityManager->getRepository(Customer::class)->findAll()[1];

        /** @var User $userInCustomer2 */
        $userInCustomer2 = $customer2->getUsers()[0];

        // run service
        $this->service->getUser($customer1, $userInCustomer2, $user->getToken());

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

    // cases where the token is invalid, expired, or missing are already tested in "GetPhonesServiceTest"
}
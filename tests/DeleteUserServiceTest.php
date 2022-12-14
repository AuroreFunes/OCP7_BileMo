<?php

namespace App\Test;

use App\Entity\Customer;
use App\Entity\User;
use App\Service\User\DeleteUserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class DeleteUserServiceTest extends KernelTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;

    private DeleteUserService $service;
    
    // UTILITIES
    private Customer $customer;
    private User     $userAdmin;


    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->service = $container->get(DeleteUserService::class);

        $this->uniqid = uniqid();

        // inits
        $this->customer = $this->entityManager->getRepository(Customer::class)->findAll()[0];
        // user admin
        foreach ($this->customer->getUsers() as $userTmp) {
            if (in_array('ROLE_ADMIN', $userTmp->getRoles())) {
                $this->userAdmin = $userTmp;
                break;
            }
        }

    }

    public function testDeleteUserOk()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());
        $userToDelete = $this->customer->getUsers()[count($this->customer->getUsers()) - 1];

        // run service
        $this->service->deleteUser($this->customer, $userToDelete, $this->userAdmin);

        // check status
        $this->assertTrue($this->service->getStatus());

        // count errors
        $this->assertEmpty($this->service->getUnserializedErrors());

        // check result
        $this->assertCount($usersNb - 1, $this->entityManager->getRepository(User::class)->findBy(['customer' => $this->customer]));

        // http code
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->service->getHttpCode());
    }

    public function testUserNotFound()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());

        // run service
        $this->service->deleteUser($this->customer, null, $this->userAdmin);

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertCount($usersNb, $this->entityManager->getRepository(User::class)->findBy(['customer' => $this->customer]));

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read errors
        $this->assertEquals("L'utilisateur n'a pas ??t?? trouv??.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->service->getHttpCode());
    }

    // cases where the user does not have the administrator role or does not belong to the right customer are already checked in "CreateUserServiceTest"
    // case where the user is not authenticated is already tested in "GetPhonesServiceTest"
}
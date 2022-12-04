<?php

namespace App\Test;

use App\Entity\Customer;
use App\Entity\User;
use App\Service\User\CreateUserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class CreateUserServiceTest extends KernelTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;

    private CreateUserService $service;
    
    // UTILITIES
    private string $uniqid;
    private Customer $customer;
    private User     $userAdmin;
    private User     $user;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->service = $container->get(CreateUserService::class);

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

        // user without admin role
        foreach ($this->customer->getUsers() as $userTmp) {
            if (!in_array('ROLE_ADMIN', $userTmp->getRoles())) {
                $this->user = $userTmp;
                break;
            }
        }
    }

    public function testCreateUserOk()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());

        // run service
        $this->service->createUser($this->customer, $this->giveValidUser(), $this->userAdmin->getToken());

        // check status
        $this->assertTrue($this->service->getStatus());

        // count errors
        $this->assertEmpty($this->service->getUnserializedErrors());

        // check result
        /** @var User $newUser */
        $newUser = $this->service->getUnserializedDatas();
        $this->assertEquals("New user " . $this->uniqid, $newUser->getUsername());
        $this->assertEquals($this->uniqid . "@mail.test", $newUser->getEmail());

        // http code
        $this->assertEquals(Response::HTTP_CREATED, $this->service->getHttpCode());
    }

    public function testMailAndUsernameEmpty()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());

        // run service
        $this->service->createUser($this->customer, $this->giveUsernameAndMailEmpty(), $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());
        $this->assertCount($usersNb, $this->customer->getUsers());

        // count errors
        $this->assertCount(2, $this->service->getUnserializedErrors());

        // read errors
        $this->assertEquals("Le nom d'utilisateur doit être renseigné.", $this->service->getUnserializedErrors()->get(0));
        $this->assertEquals("L'adresse e-mail doit être renseignée.", $this->service->getUnserializedErrors()->get(1));

        // http code
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->service->getHttpCode());
    }

    public function testInvalidMail()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());

        // run service
        $this->service->createUser($this->customer, $this->giveInvalidMail(), $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());
        $this->assertCount($usersNb, $this->customer->getUsers());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read errors
        $this->assertEquals("L'adresse e-mail n'est pas valide.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->service->getHttpCode());
    }

    public function testInvalidRole()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());

        // run service
        $this->service->createUser($this->customer, $this->giveInvalidRole(), $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());
        $this->assertCount($usersNb, $this->customer->getUsers());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read errors
        $this->assertEquals("Ce rôle n'est pas pris en charge : ROLE_INVALID", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->service->getHttpCode());
    }

    public function testUsernameAndMailAlreadyUsed()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());

        // run service
        $this->service->createUser($this->customer, $this->giveUsedUsernameAndEmail(), $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());
        $this->assertCount($usersNb, $this->customer->getUsers());

        // count errors
        $this->assertCount(2, $this->service->getUnserializedErrors());

        // read errors
        $this->assertEquals("Ce nom d'utilisateur existe déjà.", $this->service->getUnserializedErrors()->get(0));
        $this->assertEquals("Cet e-mail est déjà utilisé.", $this->service->getUnserializedErrors()->get(1));

        // http code
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->service->getHttpCode());
    }

    public function testNoData()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());

        // run service
        $this->service->createUser($this->customer, null, $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());
        $this->assertCount($usersNb, $this->customer->getUsers());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read errors
        $this->assertEquals("Les données doivent être fournies.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->service->getHttpCode());
    }

    public function testUserIsNotAdmin()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());

        // run service
        $this->service->createUser($this->customer, $this->giveValidUser(), $this->user->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());
        $this->assertCount($usersNb, $this->customer->getUsers());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read errors
        $this->assertEquals("Vous n'avez pas un niveau d'accès suffisant.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->service->getHttpCode());
    }

    public function testUserIsOwnedByWrongCustomer()
    {
        // init before run
        $usersNb = count($this->customer->getUsers());
        /** @var Customer $customer2 */
        $customer2 = $this->entityManager->getRepository(Customer::class)->findAll()[1];

        // run service
        $this->service->createUser($customer2, $this->giveValidUser(), $this->user->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());
        $this->assertCount($usersNb, $this->customer->getUsers());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read error
        $this->assertEquals("Vous ne pouvez pas effectuer cette opération confidentielle.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->service->getHttpCode());
    }

    // cases where the token is invalid, expired, or missing are already tested in "GetPhonesServiceTest"

    // ============================================================================================
    // DATAS FOR TESTS
    // ============================================================================================
    private function giveValidUser()
    {
        return '{
            "username": "New user ' . $this->uniqid . '",
            "email": "' . $this->uniqid . '@mail.test",
            "roles": [
               "ROLE_USER"
            ]
        }';
    }

    private function giveUsernameAndMailEmpty() {
        return '{
            "roles": [
               "ROLE_USER"
            ]
        }';
    }

    private function giveInvalidMail()
    {
        return '{
            "username": "New user ' . $this->uniqid . '",
            "email": "' . $this->uniqid . '@mail",
            "roles": [
               "ROLE_USER"
            ]
        }';
    }

    private function giveInvalidRole()
    {
        return '{
            "username": "New user ' . $this->uniqid . '",
            "email": "' . $this->uniqid . '@mail.test",
            "roles": [
               "ROLE_INVALID"
            ]
        }';
    }

    private function giveUsedUsernameAndEmail()
    {
        return '{
            "username": "User 1",
            "email": "contact1@testmail.com",
            "roles": [
               "ROLE_USER"
            ]
        }';
    }

}
<?php

namespace App\Test;

use App\Entity\Customer;
use App\Entity\User;
use App\Service\User\UpdateUserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserServiceTest extends KernelTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;

    private UpdateUserService $service;
    
    // UTILITIES
    private string $uniqid;
    private Customer $customer;
    private User     $userAdmin;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->service = $container->get(UpdateUserService::class);

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

    public function testUpdateUserOk()
    {
        // init before run
        $userToUpdate = $this->customer->getUsers()[count($this->customer->getUsers()) - 1];

        // run service
        $this->service->updateUser($this->customer, $userToUpdate, $this->giveFullDatas(), $this->userAdmin->getToken());

        // check status
        $this->assertTrue($this->service->getStatus());

        // count errors
        $this->assertEmpty($this->service->getUnserializedErrors());

        // check result
        /** @var User $newUser */
        $updatedUser = $this->service->getUnserializedDatas();
        $this->assertEquals("Modified user " . $this->uniqid, $updatedUser->getUsername());
        $this->assertEquals($this->uniqid . "@mail.test", $updatedUser->getEmail());

        // check message success
        $this->assertEquals(json_encode("La mise à jour a été effectuée avec succès."), $this->service->getDatas());

        // http code
        $this->assertEquals(Response::HTTP_OK, $this->service->getHttpCode());
    }

    public function testUpdateUsernameOnlyOk()
    {
        // init before run
        $userToUpdate = $this->customer->getUsers()[count($this->customer->getUsers()) - 1];

        // run service
        $this->service->updateUser($this->customer, $userToUpdate, $this->giveUsernameOnly(), $this->userAdmin->getToken());

        // check status
        $this->assertTrue($this->service->getStatus());

        // count errors
        $this->assertEmpty($this->service->getUnserializedErrors());

        // check result
        /** @var User $newUser */
        $updatedUser = $this->service->getUnserializedDatas();
        $this->assertEquals("Still modified user " . $this->uniqid, $updatedUser->getUsername());
        $this->assertEquals($userToUpdate->getEmail(), $updatedUser->getEmail());

        // check message success
        $this->assertEquals(json_encode("La mise à jour a été effectuée avec succès."), $this->service->getDatas());

        // http code
        $this->assertEquals(Response::HTTP_OK, $this->service->getHttpCode());
    }

    public function testInvalidMail()
    {
        // init before run
        $userToUpdate = $this->customer->getUsers()[count($this->customer->getUsers()) - 1];

        // run service
        $this->service->updateUser($this->customer,  $userToUpdate, $this->giveInvalidMail(), $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());

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
        $userToUpdate = $this->customer->getUsers()[count($this->customer->getUsers()) - 1];

        // run service
        $this->service->updateUser($this->customer, $userToUpdate, $this->giveInvalidRole(), $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());

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
        $userToUpdate = $this->customer->getUsers()[count($this->customer->getUsers()) - 1];

        // run service
        $this->service->updateUser($this->customer, $userToUpdate, $this->giveUsedUsernameAndEmail(), $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());

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
        $userToUpdate = $this->customer->getUsers()[count($this->customer->getUsers()) - 1];

        // run service
        $this->service->updateUser($this->customer, $userToUpdate, null, $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read errors
        $this->assertEquals("Les données doivent être fournies.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->service->getHttpCode());
    }

    public function testUsernotFound()
    {
        // run service
        $this->service->updateUser($this->customer, null, $this->giveUsedUsernameAndEmail(), $this->userAdmin->getToken());

        // check status
        $this->assertFalse($this->service->getStatus());

        // check result
        $this->assertEmpty($this->service->getUnserializedDatas());

        // count errors
        $this->assertCount(1, $this->service->getUnserializedErrors());

        // read errors
        $this->assertEquals("L'utilisateur n'a pas été trouvé.", $this->service->getUnserializedErrors()->get(0));

        // http code
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->service->getHttpCode());
    }

    // cases where the user does not have the administrator role or does not belong to the right client are already checked in "CreateUserServiceTest"
    // cases where the token is invalid, expired, or missing are already tested in "GetPhonesServiceTest"

    // ============================================================================================
    // DATAS FOR TESTS
    // ============================================================================================
    private function giveFullDatas()
    {
        return '{
            "username": "Modified user ' . $this->uniqid . '",
            "email": "' . $this->uniqid . '@mail.test",
            "roles": [
               "ROLE_USER"
            ]
        }';
    }

    private function giveUsernameOnly() {
        return '{
            "username": "Still modified user ' . $this->uniqid . '"
        }';
    }

    private function giveInvalidMail()
    {
        return '{
            "email": "' . $this->uniqid . '@mail"
        }';
    }

    private function giveInvalidRole()
    {
        return '{
            "roles": [
               "ROLE_INVALID"
            ]
        }';
    }

    private function giveUsedUsernameAndEmail()
    {
        return '{
            "username": "User 1",
            "email": "contact1@testmail.com"
        }';
    }

}
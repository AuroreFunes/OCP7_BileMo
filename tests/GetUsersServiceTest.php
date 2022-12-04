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
    }

    public function testGetUsersOk()
    {
        // inits before run
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->findAll()[0];
        /** @var User $user */
        $user = $customer->getUsers()[0];

        $usersNbInMyPage = count($this->entityManager->getRepository(User::class)->findAllInPage($customer->getId(), 1));

        // run service
        $this->service->getUsers($customer, $user->getToken());

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
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->findAll()[0];
        /** @var User $user */
        $user = $customer->getUsers()[0];

        $maxPage = count($customer->getUsers()) / $this->params->get('users_per_page');

        // run service
        $this->service->getUsers($customer, $user->getToken(), $maxPage + 2);

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
        // inits before run
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->findAll()[0];
        /** @var User $user */
        $user = $customer->getUsers()[0];

        // run service
        $this->service->getUsers($customer, $user->getToken(), -1);

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
        /** @var Customer $customer */
        $customer1 = $this->entityManager->getRepository(Customer::class)->findAll()[0];
        /** @var Customer $customer */
        $customer2 = $this->entityManager->getRepository(Customer::class)->findAll()[1];

        /** @var User $userInCustomer2 */
        $userInCustomer2 = $customer2->getUsers()[0];

        // run service
        $this->service->getUsers($customer1, $userInCustomer2->getToken());

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
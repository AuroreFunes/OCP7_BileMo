<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Service\User\CreateUserService;
use App\Service\User\DeleteUserService;
use App\Service\User\GetUserDetailsService;
use App\Service\User\GetUsersService;
use App\Service\User\UpdateUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class UserController extends AbstractController
{

    /**
     * @Route(
     *  "customers/{customer}/users", 
     *  name="app_users",
     *  methods={"GET"}
     * )
     */
    public function getUsers(?Customer $customer, Request $request, GetUsersService $service)
    {
        $token = UserTools::giveToken($request);

        $service->getUsers($customer, $token);

        return $service->getJsonResponse([]);
    }

    /**
     * @Route(
     *  "customers/{customer}/users/{user}", 
     *  name="app_users_details",
     *  methods={"GET"}
     * )
     */
    public function getUserDetails(Customer $customer, User $user, Request $request, GetUserDetailsService $service)
    {
        $token = UserTools::giveToken($request);

        $service->getUser($customer, $user, $token);

        return $service->getJsonResponse([]);
    }

    /**
     * @Route(
     *  "customers/{customer}/users", 
     *  name="app_users_create",
     *  methods={"POST"}
     * )
     */
    public function createUser(?Customer $customer, Request $request, CreateUserService $service)
    {
        $token = UserTools::giveToken($request);

        $service->createUser($customer, $request->getContent(), $token);

        return $service->getJsonResponse([]);
    }

    /**
     * @Route(
     *  "customers/{customer}/users/{user}", 
     *  name="app_users_delete",
     *  methods={"DELETE"}
     * )
     */
    public function deleteUser(?Customer $customer, ?User $user, Request $request, DeleteUserService $service)
    {
        $token = UserTools::giveToken($request);

        $service->deleteUser($customer, $user, $token);

        return $service->getJsonResponse([]);
    }

    /**
     * @Route(
     *  "customers/{customer}/users/{user}", 
     *  name="app_users_update",
     *  methods={"PUT"}
     * )
     */
    public function updateUser(?Customer $customer, ?User $user, Request $request, UpdateUserService $service)
    {
        $token = UserTools::giveToken($request);

        $service->updateUser($customer, $user, $request->getContent(), $token);

        return $service->getJsonResponse([]);
    }

}
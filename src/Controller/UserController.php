<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Service\User\CreateUserService;
use App\Service\User\DeleteUserService;
use App\Service\User\GetUserDetailsService;
use App\Service\User\GetUsersService;
use App\Service\User\UpdateUserService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class UserController extends AbstractController
{

    /**
     * Returns the users belonging to a customer for the specified page (or page 1 if the page number is not provided)
     * 
     * @OA\Tag(name="Users")
     * @OA\Response(
     *     response=200,
     *     description="Returns the users belonging to a customer for the specified page (or page 1 if the page number is not provided)",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page number",
     *     @OA\Schema(type="int")
     * )
     * 
     * @Route("api/customers/{customer}/users", name="api_users", methods={"GET"})
     */
    public function getUsers(?Customer $customer, GetUsersService $service)
    {
        $service->getUsers($customer, $this->getUser());

        return $service->getJsonResponse([]);
    }

    /**
     * Gives the details of a user (belonging to a customer)
     * 
     * @OA\Tag(name="Users")
     * @OA\Response(
     *     response=200,
     *     description="Gives the details of a user (belonging to a customer)",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUserDetails"}))
     *     )
     * )
     * 
     * @Route("api/customers/{customer}/users/{user}", name="api_users_details", methods={"GET"})
     */
    public function getUserDetails(Customer $customer, User $user, GetUserDetailsService $service)
    {
        $service->getUser($customer, $user, $this->getUser());

        return $service->getJsonResponse([]);
    }

    /**
     * Created a user (belonging to the same customer)
     * 
     * @OA\Tag(name="Users")
     * @OA\Response(
     *     response=201,
     *     description="Created a user (belonging to the same customer)",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUserDetails"}))
     *     )
     * )
     * @OA\RequestBody(
     *  required=true,
     *    @OA\MediaType(
     *      mediaType="application/json",
     *        @OA\Schema(
     *          @OA\Property(property="fullName", description="The name of the user.", type="string", example="User 1"),
     *          @OA\Property(property="password", description="Password of the user.", type="string", format="password", example="Abcd1234"),
     *          @OA\Property(property="email", description="Email of the user.", type="string", format="email", example="user1@bilemo.com"),
     *          @OA\Property(property="roles", description="Roles of the user.", type="array", format="array",
     *            @OA\Items(type="string", example="ROLE_USER")
     *          )
     *        )
     *      )
     *    )
     * )
     * 
     * @Route("api/customers/{customer}/users", name="api_users_create", methods={"POST"})
     * IsGaranted("ROLE_ADMIN", message: "Vous n'avez pas un niveau d'accès suffisant pour effectuer cette opération.")
     */
    public function createUser(?Customer $customer, Request $request, CreateUserService $service)
    {
        $service->createUser($customer, $request->getContent(), $this->getUser());

        return $service->getJsonResponse([]);
    }

    /**
     * Deleted a user (belonging to the same customer)
     * 
     * @OA\Tag(name="Users")
     * @OA\Response(
     *     response=204,
     *     description="Deleted a user (belonging to the same customer)"
     * )
     * 
     * @Route("api/customers/{customer}/users/{user}", name="api_users_delete", methods={"DELETE"})
     * IsGaranted("ROLE_ADMIN", message: "Vous n'avez pas un niveau d'accès suffisant pour effectuer cette opération.")
     */
    public function deleteUser(?Customer $customer, ?User $user, Request $request, DeleteUserService $service)
    {
        $service->deleteUser($customer, $user, $this->getUser());

        return $service->getJsonResponse([]);
    }

    /**
     * Updated a user (belonging to the same customer)
     * 
     * @OA\Tag(name="Users")
     * @OA\Response(
     *     response=200,
     *     description="Updated a user (belonging to the same customer)",
     * )
     * @OA\RequestBody(
     *  required=false,
     *    @OA\MediaType(
     *      mediaType="application/json",
     *        @OA\Schema(
     *          @OA\Property(property="fullName", description="The name of the user.", type="string", example="User 1"),
     *          @OA\Property(property="password", description="Password of the user.", type="string", format="password", example="Abcd1234"),
     *          @OA\Property(property="email", description="Email of the user.", type="string", format="email", example="user1@bilemo.com"),
     *          @OA\Property(property="roles", description="Roles of the user.", type="array", format="array",
     *            @OA\Items(type="string", example="ROLE_USER")
     *          )
     *        )
     *      )
     *    )
     * )
     * 
     * @Route("api/customers/{customer}/users/{user}", name="api_users_update", methods={"PUT"})
     * IsGaranted("ROLE_ADMIN", message: "Vous n'avez pas un niveau d'accès suffisant pour effectuer cette opération.")
     */
    public function updateUser(?Customer $customer, ?User $user, Request $request, UpdateUserService $service)
    {
        $service->updateUser($customer, $user, $request->getContent(), $this->getUser());

        return $service->getJsonResponse([]);
    }

}
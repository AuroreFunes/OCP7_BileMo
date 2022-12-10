<?php

namespace App\Controller;

use App\Entity\Phone;
use App\Service\Phone\GetPhoneDetailsService;
use App\Service\Phone\GetPhonesService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PhoneController extends AbstractController
{

    /**
     * Gives the phones of the specified page (or of page 1 if the page number is not provided)
     * 
     * @OA\Tag(name="Phones")
     * @OA\Response(
     *     response=200,
     *     description="Gives the phones of the specified page (or of page 1 if the page number is not provided)",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phone::class, groups={"getPhones"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page number",
     *     @OA\Schema(type="int")
     * )
     * 
     * @Route("api/phones", name="api_phones", methods={"GET"})
     */
    public function getAllPhones(Request $request, GetPhonesService $service): JsonResponse
    {
        $page = $request->get('page', 1);

        // check user, then get phones
        $service->getAllPhones($page, $this->getUser());

        return $service->getJsonResponse();
    }


    /**
     * Gives the details of the specified model
     * 
     * @OA\Tag(name="Phones")
     * @OA\Response(
     *     response=200,
     *     description="Gives the details of the specified model",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phone::class, groups={"getPhoneDetails"}))
     *     )
     * )
     * 
     * @Route("api/phones/{id}", name="api_phones_details", methods={"GET"})
     */
    public function getDetailsPhone(?Phone $phone, GetPhoneDetailsService $service)
    {
        $service->getPhone($phone, $this->getUser());

        return $service->getJsonResponse([]);
    }

}
<?php

namespace App\Controller;

use App\Entity\Phone;
use App\Service\Phone\GetPhoneDetailsService;
use App\Service\Phone\GetPhonesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PhoneController extends AbstractController
{

    /**
     * @Route("phones", name="app_phones", methods={"GET"})
     */
    public function getAllPhones(Request $request, GetPhonesService $service): JsonResponse
    {
        $page = $request->get('page', 1);
        // gives the token from the request header
        $token = UserTools::giveToken($request);

        // check user, then get phones
        $service->getAllPhones($page, $token);

        return $service->getJsonResponse();
    }


    /**
     * @Route("phones/{id}", name="app_phones_details", methods={"GET"})
     */
    public function getDetailsPhone(?Phone $phone, Request $request, GetPhoneDetailsService $service)
    {
        // gives the token from the request header
        $token = UserTools::giveToken($request);

        $service->getPhone($phone, $token);

        return $service->getJsonResponse([]);
    }

}
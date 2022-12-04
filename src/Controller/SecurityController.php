<?php

namespace App\Controller;

use App\Entity\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{

    // ============================================================================================
    // CONNECTION WITH GOOGLE
    // ============================================================================================

    /**
     * @Route("/connect/google", name="oauth_connect_google")
     */
    public function connectGoogle(ClientRegistry $clientRegistry): RedirectResponse
    {
        // redirect to Google
        return $clientRegistry->getClient('google')->redirect([], []);
    }

    /**
     * @Route("/oauth/check/google", name="oauth_check_google")
     */
    public function connectCheckGoogle(Request $request, ClientRegistry $clientRegistry)
    {

    }

    // ============================================================================================
    // SEND TOKEN
    // ============================================================================================

    /**
     * @Route("/oauth/token", name="oauth_give_token")
     */
    public function testConnect(Request $request)
    {
        return new JsonResponse(['token' => $request->query->get('token')], Response::HTTP_OK);
    }

}
<?php

namespace App\Controller;

use App\Service\TurnstileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, TurnstileService $turnstileService): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Validate Turnstile on POST request
        if ($request->isMethod('POST')) {
            $turnstileResponse = $request->request->get('cf-turnstile-response');
            
            if (!$turnstileService->verify($turnstileResponse)) {
                $this->addFlash('error', 'Veuillez compléter la vérification de sécurité.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'turnstile_site_key' => $turnstileService->getSiteKey(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

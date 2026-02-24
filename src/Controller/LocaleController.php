<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'app_locale_switch')]
    public function switchLocale(Request $request, string $locale): Response
    {
        // Valider la langue
        $supportedLocales = ['fr', 'en'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = 'fr';
        }

        // Sauvegarder en session
        $request->getSession()->set('_locale', $locale);

        // Rediriger vers la page précédente
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('accueil');
    }
}

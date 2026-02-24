<?php

namespace App\Controller;

use App\Service\BrevoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/notify', name: 'notify')]
    public function notify(BrevoService $brevo): Response
    {
        $email = "client@test.com";
        $phone = "+21612345678";

        // EMAIL
        $brevo->sendEmail(
            $email,
            "Client",
            "Paiement réussi",
            "<h2>Paiement confirmé ✅</h2><p>Merci pour votre réservation parking.</p>"
        );

        // SMS
        $brevo->sendSMS(
            $phone,
            "Votre paiement est confirmé. Merci pour votre réservation !"
        );

        return new Response('Email + SMS envoyés avec Brevo');
    }
}

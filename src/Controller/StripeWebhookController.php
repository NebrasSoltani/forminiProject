<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Service\CallMeBotWhatsappSender;
use App\Service\SendGridEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        EntityManagerInterface $em,
        SendGridEmailSender $sendGrid,
        CallMeBotWhatsappSender $smsSender,
        Environment $twig,
        LoggerInterface $logger
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        $stripeSecret = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        if ($stripeSecret) {
            Stripe::setApiKey($stripeSecret);
        }

        if (!$webhookSecret || !$sigHeader) {
            return new Response('Invalid signature config', 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            $logger->error('Stripe webhook payload invalid: ' . $e->getMessage());
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $logger->error('Stripe webhook signature invalid: ' . $e->getMessage());
            return new Response('Invalid signature', 400);
        }

        if (($event->type ?? null) === 'checkout.session.completed') {
            $session = $event->data->object ?? null;
            $inscriptionId = $session->metadata->inscription_id ?? $session->client_reference_id ?? null;

            if ($inscriptionId) {
                $inscription = $em->getRepository(Inscription::class)->find((int) $inscriptionId);
                if ($inscription) {
                    $inscription->setModePaiement('carte');
                    $em->flush();

                    $user = $inscription->getApprenant();
                    $html = $twig->render('emails/inscription_success.html.twig', [
                        'inscription' => $inscription,
                        'user' => $user,
                    ]);

                    try {
                        $email = $sendGrid->createEmail(
                            $user->getEmail(),
                            $user->getPrenom() ?? $user->getUserIdentifier(),
                            'Inscription confirmee - Formini',
                            $html
                        );
                        $sendGrid->send($email);
                        $logger->info('Webhook: Email sent via SendGrid to ' . $user->getEmail());
                    } catch (\Throwable $e) {
                        $logger->error('Webhook: Email send failed via SendGrid: ' . $e->getMessage());
                    }

                    try {
                        if ($user && $user->getTelephone()) {
                            $smsSender->send(
                                (string) $user->getTelephone(),
                                sprintf(
                                    'Paiement confirme. Votre inscription "%s" est validee. Merci.',
                                    $inscription->getFormation()->getTitre()
                                )
                            );
                            $logger->info('Webhook: WhatsApp sent via CallMeBot to ' . $user->getTelephone());
                        } else {
                            $logger->warning('Webhook: SMS skipped, missing phone number.');
                        }
                    } catch (\Throwable $e) {
                        $logger->error('Webhook: WhatsApp send failed via CallMeBot: ' . $e->getMessage());
                    }
                }
            }
        }

        return new Response('OK', 200);
    }
}

<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Entity\Inscription;
use App\Entity\Produit;
use App\Service\CallMeBotWhatsappSender;
use App\Service\SendGridEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaiementController extends AbstractController
{
    #[Route('/paiement/panier/checkout', name: 'payment_cart_checkout', methods: ['POST'])]
    public function checkoutPanier(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('place_order', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $panier = $session->get('panier', []);
        if (!$panier) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('boutique_panier');
        }

        $commande = new Commande();
        $commande->setUtilisateur($this->getUser());
        $commande->setStatut('en_attente');

        foreach ($panier as $id => $itemData) {
            $produit = $em->getRepository(Produit::class)->find($id);
            if (!$produit) {
                continue;
            }

            $quantite = (int) ($itemData['quantite'] ?? 0);
            if ($quantite <= 0) {
                continue;
            }

            if ($produit->getStock() < $quantite) {
                $this->addFlash('error', 'Stock insuffisant pour ' . $produit->getNom());
                return $this->redirectToRoute('boutique_panier');
            }

            $item = new CommandeItem();
            $item->setProduit($produit);
            $item->setNomProduit($produit->getNom());
            $item->setQuantite($quantite);
            $item->setPrixUnitaire($produit->getPrix());
            $commande->addItem($item);
        }

        if ($commande->getItems()->count() === 0) {
            $this->addFlash('error', 'Votre panier ne contient aucun produit valide.');
            return $this->redirectToRoute('boutique_panier');
        }

        $commande->calculerTotal();
        $em->persist($commande);
        $em->flush();

        $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        if (!$stripeKey) {
            $this->addFlash('error', 'Cle Stripe manquante.');
            return $this->redirectToRoute('boutique_panier');
        }

        Stripe::setApiKey($stripeKey);

        $lineItems = [];
        foreach ($commande->getItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->getNomProduit(),
                    ],
                    'unit_amount' => (int) round($item->getPrixUnitaire() * 100),
                ],
                'quantity' => $item->getQuantite(),
            ];
        }

        $stripeSession = Session::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'client_reference_id' => (string) $commande->getId(),
            'success_url' => $this->generateUrl(
                'payment_success',
                ['id' => $commande->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'cancel_url' => $this->generateUrl(
                'payment_cancel',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'metadata' => [
                'commande_id' => (string) $commande->getId(),
            ],
        ]);

        $commande->setStripeSessionId($stripeSession->id);
        $em->flush();

        return $this->redirect($stripeSession->url);
    }

    #[Route('/paiement/success/{id}', name: 'payment_success')]
    public function success(
        int $id,
        EntityManagerInterface $em,
        SendGridEmailSender $sendGrid,
        CallMeBotWhatsappSender $smsSender,
        LoggerInterface $logger
    ): Response {
        $user = $this->getUser();
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande || $commande->getUtilisateur() !== $user) {
            throw $this->createNotFoundException('Commande non trouvee');
        }

        $commande->setStatut('payee');
        $em->flush();

        $html = $this->renderView('emails/commande_success.html.twig', [
            'commande' => $commande,
            'user' => $user,
        ]);

        $emailSent = false;

        try {
            $logger->info('Sending order confirmation email via SendGrid to ' . $user->getEmail());
            $email = $sendGrid->createEmail(
                $user->getEmail(),
                $user->getPrenom() ?? $user->getUserIdentifier(),
                'Votre commande a ete confirmee',
                $html
            );
            $sendGrid->send($email);
            $logger->info('Order email sent via SendGrid');
            $emailSent = true;
        } catch (\Throwable $e) {
            $logger->error('Order email failed via SendGrid: ' . $e->getMessage());
        }

        $smsSent = false;
        try {
            if ($user && $user->getTelephone()) {
                $smsSender->send(
                    (string) $user->getTelephone(),
                    sprintf(
                        'Paiement confirme. Commande #%d recue pour %.2f TND. Merci.',
                        (int) $commande->getId(),
                        (float) $commande->getTotal()
                    )
                );
                $logger->info('Order WhatsApp sent via CallMeBot to ' . $user->getTelephone());
                $smsSent = true;
            } else {
                $logger->warning('Order SMS skipped: missing user phone number.');
            }
        } catch (\Throwable $e) {
            $logger->error('Order WhatsApp failed via CallMeBot: ' . $e->getMessage());
        }

        if ($emailSent && $smsSent) {
            $this->addFlash('success', 'Paiement confirme. Email et SMS de confirmation envoyes.');
        } elseif ($emailSent) {
            $this->addFlash('warning', 'Paiement confirme. Email envoye, mais SMS non envoye.');
        } elseif ($smsSent) {
            $this->addFlash('warning', 'Paiement confirme. SMS envoye, mais email non envoye.');
        } else {
            $this->addFlash('error', 'Paiement confirme, mais ni email ni SMS n\'ont pu etre envoyes.');
        }

        return $this->render('payment/success.html.twig', [
            'commande' => $commande,
            'user' => $user,
        ]);
    }

    #[Route('/paiement/inscription/{id}', name: 'paiement_inscription', methods: ['GET'])]
    public function checkoutInscription(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $inscription = $em->getRepository(Inscription::class)->find($id);
        $user = $this->getUser();

        if (!$inscription || $inscription->getApprenant() !== $user) {
            throw $this->createNotFoundException('Inscription non trouvee');
        }

        if ($inscription->getModePaiement() !== 'en_attente') {
            $this->addFlash('warning', 'Paiement deja en cours ou effectue.');
            return $this->redirectToRoute('apprenant_mes_formations');
        }

        $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        if (!$stripeKey) {
            $this->addFlash('error', 'Cle Stripe manquante.');
            return $this->redirectToRoute('apprenant_mes_formations');
        }

        Stripe::setApiKey($stripeKey);

        $formation = $inscription->getFormation();
        $montant = (float) $inscription->getMontantPaye();

        $stripeSession = Session::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => $formation->getTitre()],
                    'unit_amount' => (int) round($montant * 100),
                ],
                'quantity' => 1,
            ]],
            'client_reference_id' => (string) $inscription->getId(),
            'success_url' => $this->generateUrl('paiement_inscription_success', ['id' => $inscription->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('apprenant_formation_show', ['id' => $formation->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => ['inscription_id' => (string) $inscription->getId()],
        ]);

        return $this->redirect($stripeSession->url);
    }

    #[Route('/paiement/inscription/success/{id}', name: 'paiement_inscription_success')]
    public function successInscription(
        int $id,
        EntityManagerInterface $em,
        SendGridEmailSender $sendGrid,
        CallMeBotWhatsappSender $smsSender,
        LoggerInterface $logger
    ): Response {
        $user = $this->getUser();
        $inscription = $em->getRepository(Inscription::class)->find($id);

        if (!$inscription || $inscription->getApprenant() !== $user) {
            throw $this->createNotFoundException('Inscription non trouvee');
        }

        $inscription->setModePaiement('carte');
        $em->flush();

        $html = $this->renderView('emails/inscription_success.html.twig', [
            'inscription' => $inscription,
            'user' => $user,
        ]);

        $emailSent = false;

        try {
            $logger->info('Sending inscription email via SendGrid to: ' . $user->getEmail());
            $email = $sendGrid->createEmail(
                $user->getEmail(),
                $user->getPrenom() ?? $user->getUserIdentifier(),
                'Inscription confirmee',
                $html
            );
            $sendGrid->send($email);
            $logger->info('Inscription email sent via SendGrid');
            $emailSent = true;
        } catch (\Throwable $e) {
            $logger->error('Inscription email failed via SendGrid: ' . $e->getMessage());
        }

        $smsSent = false;
        try {
            if ($user && $user->getTelephone()) {
                $smsSender->send(
                    (string) $user->getTelephone(),
                    sprintf(
                        'Paiement confirme. Votre inscription "%s" est validee. Merci.',
                        $inscription->getFormation()->getTitre()
                    )
                );
                $logger->info('Inscription WhatsApp sent via CallMeBot to ' . $user->getTelephone());
                $smsSent = true;
            } else {
                $logger->warning('Inscription SMS skipped: missing user phone number.');
            }
        } catch (\Throwable $e) {
            $logger->error('Inscription WhatsApp failed via CallMeBot: ' . $e->getMessage());
        }

        if ($emailSent && $smsSent) {
            $this->addFlash('success', 'Paiement confirme. Email et SMS de confirmation envoyes.');
        } elseif ($emailSent) {
            $this->addFlash('warning', 'Paiement confirme. Email envoye, mais SMS non envoye.');
        } elseif ($smsSent) {
            $this->addFlash('warning', 'Paiement confirme. SMS envoye, mais email non envoye.');
        } else {
            $this->addFlash('error', 'Paiement confirme, mais ni email ni SMS n\'ont pu etre envoyes.');
        }

        return $this->redirectToRoute('apprenant_mes_formations');
    }

    #[Route('/paiement/annule', name: 'payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}

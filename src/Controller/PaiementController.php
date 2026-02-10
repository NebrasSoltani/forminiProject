<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Webhook;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaiementController extends AbstractController
{
    #[Route('/produit/{id}/acheter', name: 'acheter_produit')]
    public function acheter(Produit $produit, EntityManagerInterface $em)
    {
        $commande = new Commande();
        $commande->setStatut('en_attente');
        $commande->setTotal($produit->getPrix());
        $commande->setUtilisateur($this->getUser());

        $item = new CommandeItem();
        $item->setProduit($produit);
        $item->setNomProduit($produit->getNom());
        $item->setQuantite(1);
        $item->setPrixUnitaire($produit->getPrix());
        $commande->addItem($item);

        $em->persist($commande);
        $em->flush();

        return $this->redirectToRoute('checkout', [
            'id' => $commande->getId()
        ]);
    }

    #[Route('/checkout/{id}', name: 'checkout')]
    public function checkout(Commande $commande, EntityManagerInterface $em)
    {
        Stripe::setApiKey((string) $_ENV['STRIPE_SECRET_KEY']);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'tnd', // Devise en dinar tunisien
                    'product_data' => [
                        'name' => 'Achat de produit',
                    ],
                    'unit_amount' => (int) round(((float) $commande->getTotal()) * 100), // en centimes
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $commande->setStripeSessionId($session->id);
        $em->flush();

        return $this->redirect($session->url);
    }

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
            if (!$produit) continue;

            $quantite = (int) ($itemData['quantite'] ?? 0);
            if ($quantite <= 0) continue;

            if ($produit->getStock() < $quantite) {
                $this->addFlash('error', 'Stock insuffisant pour ' . $produit->getNom());
                return $this->redirectToRoute('boutique_panier');
            }

            $orderItem = new CommandeItem();
            $orderItem->setProduit($produit);
            $orderItem->setNomProduit($produit->getNom());
            $orderItem->setQuantite($quantite);
            $orderItem->setPrixUnitaire($produit->getPrix());
            $commande->addItem($orderItem);
        }

        if ($commande->getItems()->count() === 0) {
            $this->addFlash('error', 'Votre panier ne contient aucun produit valide.');
            return $this->redirectToRoute('boutique_panier');
        }

        $commande->calculerTotal();
        $em->persist($commande);
        $em->flush();

        Stripe::setApiKey((string) $_ENV['STRIPE_SECRET_KEY']);

        $lineItems = [];
        foreach ($commande->getItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->getNomProduit(),
                    ],
                    'unit_amount' => (int) round((float)$item->getPrixUnitaire() * 100), // centimes
                ],
                'quantity' => $item->getQuantite(),
            ];
        }

        $stripeSession = Session::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'client_reference_id' => (string) $commande->getReference(),
            'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'commande_id' => (string) $commande->getId(),
            ],
        ]);

        $commande->setStripeSessionId($stripeSession->id);
        $em->flush();

        return $this->redirect($stripeSession->url);
    }

    #[Route('/paiement/success', name: 'payment_success', methods: ['GET'])]
    public function success(Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $stripeSessionId = (string) $request->query->get('session_id', '');
        $commande = null;
        if ($stripeSessionId !== '') {
            $commande = $em->getRepository(Commande::class)->findOneBy(['stripeSessionId' => $stripeSessionId]);
        }

        if ($commande && $commande->getStatut() === 'paye') {
            $session->remove('panier');
        }

        return $this->render('payment/success.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/paiement/annule', name: 'payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(Request $request, EntityManagerInterface $em): Response
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->headers->get('stripe-signature');

        $webhookSecret = (string) ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');
        if ($webhookSecret === '') {
            return new Response('Clé Webhook Stripe manquante', 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            return new Response('Payload invalide', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Signature invalide', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            /** @var \Stripe\Checkout\Session $stripeSession */
            $stripeSession = $event->data->object;
            $stripeSessionId = $stripeSession->id;
            $paymentIntentId = $stripeSession->payment_intent ?? null;

            $commande = $em->getRepository(Commande::class)->findOneBy(['stripeSessionId' => $stripeSessionId]);
            if ($commande && $commande->getStatut() !== 'paye') {
                $commande->setStatut('paye');
                $commande->setStripePaymentIntentId($paymentIntentId);

                foreach ($commande->getItems() as $item) {
                    $produit = $item->getProduit();
                    $produit->setStock($produit->getStock() - $item->getQuantite());
                }

                $em->flush();
            }
        }

        return new JsonResponse(['reçu' => true]);
    }
}

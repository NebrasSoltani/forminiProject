<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Entity\Inscription;
use App\Entity\Produit;
use App\Service\CallMeBotWhatsappSender;
use App\Service\SendGridEmailSender;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
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
        $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        
        if (empty($stripeKey)) {
            $this->addFlash('error', '⚠️ Configuration Stripe manquante. Veuillez configurer STRIPE_SECRET_KEY dans le fichier .env');
            return $this->redirectToRoute('boutique_panier');
        }
        
        Stripe::setApiKey($stripeKey);

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

    #[Route('/paiement/inscription/{id}', name: 'paiement_inscription')]
    public function paiementInscription(Inscription $inscription, EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur est bien l'apprenant de cette inscription
        if ($inscription->getApprenant() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette page.');
        }

        // Vérifier la configuration Stripe
        $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        
        if (empty($stripeKey)) {
            $this->addFlash('error', '⚠️ Configuration Stripe manquante. Veuillez configurer STRIPE_SECRET_KEY dans le fichier .env');
            return $this->redirectToRoute('apprenant_mes_formations');
        }

        $formation = $inscription->getFormation();
        $prix = $formation->getPrixPromo() ?? $formation->getPrix();

        // Si la formation est gratuite (prix = 0 ou null)
        if (!$prix || (float)$prix <= 0) {
            $inscription->setModePaiement('gratuit');
            $inscription->setMontantPaye('0.00');
            $em->flush();
            
            $this->addFlash('success', '✅ Inscription confirmée pour la formation gratuite !');
            return $this->redirectToRoute('apprenant_mes_formations');
        }

        Stripe::setApiKey($stripeKey);

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $formation->getTitre(),
                            'description' => 'Inscription à la formation',
                        ],
                        'unit_amount' => (int) round(((float) $prix) * 100), // en centimes
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('payment_inscription_success', ['id' => $inscription->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('payment_inscription_cancel', ['id' => $inscription->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'metadata' => [
                    'inscription_id' => (string) $inscription->getId(),
                ],
            ]);

            // Stocker l'ID de session Stripe (si vous avez un champ pour cela)
            // $inscription->setStripeSessionId($session->id);
            $em->flush();

            return $this->redirect($session->url);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création de la session de paiement : ' . $e->getMessage());
            return $this->redirectToRoute('apprenant_mes_formations');
        }
    }

    #[Route('/paiement/inscription/{id}/success', name: 'payment_inscription_success')]
    public function inscriptionSuccess(Inscription $inscription, EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur est bien l'apprenant
        if ($inscription->getApprenant() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Mettre à jour le statut de paiement
        if ($inscription->getModePaiement() === 'en_attente') {
            $inscription->setModePaiement('carte');
            $formation = $inscription->getFormation();
            $prix = $formation->getPrixPromo() ?? $formation->getPrix();
            $inscription->setMontantPaye($prix);
            $em->flush();
        }

        $this->addFlash('success', '✅ Paiement réussi ! Votre inscription est confirmée.');
        return $this->redirectToRoute('apprenant_mes_formations');
    }

    #[Route('/paiement/inscription/{id}/cancel', name: 'payment_inscription_cancel')]
    public function inscriptionCancel(Inscription $inscription): Response
    {
        // Vérifier que l'utilisateur est bien l'apprenant
        if ($inscription->getApprenant() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->addFlash('warning', 'Le paiement a été annulé. Vous pouvez réessayer quand vous le souhaitez.');
        return $this->redirectToRoute('apprenant_mes_formations');
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
        $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        
        if (empty($stripeKey)) {
            $this->addFlash('error', '⚠️ Configuration Stripe manquante. Veuillez configurer STRIPE_SECRET_KEY dans le fichier .env');
            return $this->redirectToRoute('boutique_panier');
        }
        
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
        PdfGeneratorService $pdfGenerator,
        LoggerInterface $logger
    ): Response {
        $user = $this->getUser();
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande || $commande->getUtilisateur() !== $user) {
            throw $this->createNotFoundException('Commande non trouvee');
        }

        $commande->setStatut('payee');
        $em->flush();

        // Génération de la facture PDF
        $invoicePath = null;
        try {
            $invoicePath = $pdfGenerator->generateInvoicePdf($commande, $user);
            $logger->info('Invoice PDF generated: ' . $invoicePath);
        } catch (\Throwable $e) {
            $logger->error('Failed to generate invoice PDF: ' . $e->getMessage());
        }

        $html = $this->renderView('emails/commande_success.html.twig', [
            'commande' => $commande,
            'user' => $user,
            'invoicePath' => $invoicePath,
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
            
            // Ajouter la facture PDF en pièce jointe si elle a été générée
            if ($invoicePath && file_exists($invoicePath)) {
                $email->attach(
                    file_get_contents($invoicePath),
                    'facture_' . $commande->getId() . '.pdf',
                    'application/pdf'
                );
            }
            
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
                        'Paiement confirme. Commande #%d recue pour %.2f TND. Facture PDF envoyee par email. Merci.',
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
            $this->addFlash('success', 'Paiement confirme. Email et SMS de confirmation envoyes. Facture PDF jointe a l\'email.');
        } elseif ($emailSent) {
            $this->addFlash('warning', 'Paiement confirme. Email avec facture PDF envoye, mais SMS non envoye.');
        } elseif ($smsSent) {
            $this->addFlash('warning', 'Paiement confirme. SMS envoye, mais email non envoye.');
        } else {
            $this->addFlash('error', 'Paiement confirme, mais ni email ni SMS n\'ont pu etre envoyes.');
        }

        return $this->render('payment/success.html.twig', [
            'commande' => $commande,
            'user' => $user,
            'invoicePath' => $invoicePath ? basename($invoicePath) : null,
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

    #[Route('/facture/{id}', name: 'download_invoice')]
    public function downloadInvoice(
        int $id,
        EntityManagerInterface $em,
        PdfGeneratorService $pdfGenerator
    ): Response {
        $user = $this->getUser();
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande || $commande->getUtilisateur() !== $user) {
            throw $this->createNotFoundException('Commande non trouvee');
        }

        if ($commande->getStatut() !== 'payee') {
            throw $this->createAccessDeniedException('La facture n\'est disponible que pour les commandes payées');
        }

        try {
            $invoicePath = $pdfGenerator->generateInvoicePdf($commande, $user);
            
            if (!file_exists($invoicePath)) {
                throw $this->createNotFoundException('Fichier de facture non trouvé');
            }

            return $this->file(
                $invoicePath,
                'facture_' . $commande->getId() . '.pdf',
                'application/pdf'
            );
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de la génération de la facture: ' . $e->getMessage());
            return $this->redirectToRoute('boutique_mes_commandes');
        }
    }
}

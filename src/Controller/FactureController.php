<?php

namespace App\Controller;

// Import des classes nécessaires
use App\Entity\Commande;            // Entité Commande
use Dompdf\Dompdf;                 // Bibliothèque Dompdf pour générer les PDF
use Dompdf\Options;                // Options de Dompdf
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response; // Pour renvoyer une réponse HTTP
use Symfony\Component\Routing\Annotation\Route;

class FactureController extends AbstractController
{
    // Définition de la route pour générer le PDF d'une commande
    #[Route('/commande/{id}/pdf', name: 'commande_pdf')]
    public function generatePdf(Commande $commande): Response
    {
        // ===== Sécurité =====
        // Vérifie que l'utilisateur connecté est bien le propriétaire de la commande
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // ===== Configuration de Dompdf =====
        $options = new Options();
        $options->set('defaultFont', 'Arial'); // Police par défaut pour le PDF

        $dompdf = new Dompdf($options); // Création d'une instance Dompdf

        // ===== Génération du HTML depuis Twig =====
        // On utilise renderView au lieu de render pour récupérer le HTML sous forme de string
        $html = $this->renderView('facture/pdf.html.twig', [
            'commande' => $commande // On envoie la commande à la vue Twig
        ]);

        // ===== Chargement et rendu du PDF =====
        $dompdf->loadHtml($html);             // On charge le HTML
        $dompdf->setPaper('A4', 'portrait');  // Format A4 vertical
        $dompdf->render();                    // Génère le PDF en mémoire

        // ===== Retour de la réponse HTTP =====
        // On renvoie le PDF en téléchargement
        return new Response(
            $dompdf->output(),                // Contenu du PDF
            200,                              // Code HTTP
            [
                'Content-Type' => 'application/pdf', // Type de contenu
                // Nom du fichier dynamique avec la référence de la commande
                'Content-Disposition' => 'attachment; filename="facture-'.$commande->getReference().'.pdf"'
            ]
        );
    }
}

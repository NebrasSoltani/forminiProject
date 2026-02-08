<?php

namespace App\Controller;

use App\Entity\Commande;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FactureController extends AbstractController
{
    #[Route('/commande/{id}/pdf', name: 'commande_pdf')]
    public function generatePdf(Commande $commande): Response
    {
        
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        
        $options = new Options();
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        // html depuis twig
        $html = $this->renderView('facture/pdf.html.twig', [
            'commande' => $commande
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="facture-'.$commande->getReference().'.pdf"'
            ]
        );
    }
}

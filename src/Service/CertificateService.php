<?php

namespace App\Service;

use App\Entity\ResultatQuiz;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

class CertificateService
{
    public function __construct(
        private readonly Environment $twig,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {}

    public function generateCertificate(ResultatQuiz $resultat): string
    {
        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true); // Allow loading images
        
        $dompdf = new Dompdf($options);

        // Render HTML
        $html = $this->twig->render('certificate/template.html.twig', [
            'resultat' => $resultat,
            'image_dir' => $this->projectDir . '/public/images', // Helper for local images
        ]);

        $dompdf->loadHtml($html);

        // Setup paper size
        $dompdf->setPaper('A4', 'landscape');

        // Render PDF
        $dompdf->render();

        return $dompdf->output();
    }
}

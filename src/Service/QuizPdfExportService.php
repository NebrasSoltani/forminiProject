<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Entity\Formation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * ═══════════════════════════════════════════════════════════════════
 *  BUNDLE 3 — Dompdf : Service d'export PDF pour les quiz
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Ce service utilise le bundle externe dompdf/dompdf pour générer
 *  des fichiers PDF à partir de templates Twig.
 *
 *  Utilisation dans QuizController :
 *    - exportStatistiquesPdf()  → génère un PDF des stats du quiz
 *    - exportResultatsPdf()     → génère un PDF des résultats apprenants
 */
class QuizPdfExportService
{
    public function __construct(
        private readonly Environment $twig
    ) {}

    /**
     * Exporte les statistiques d'un quiz en PDF.
     */
    public function exportStatistiques(
        Quiz $quiz,
        Formation $formation,
        int $totalQuestions,
        int $totalReponses,
        int $bonnesReponses,
        int $questionsValides,
        int $questionsInvalides,
        float $pourcentageValide
    ): Response
    {
        $html = $this->twig->render('pdf/quiz_statistiques.html.twig', [
            'quiz' => $quiz,
            'formation' => $formation,
            'totalQuestions' => $totalQuestions,
            'totalReponses' => $totalReponses,
            'bonnesReponses' => $bonnesReponses,
            'questionsValides' => $questionsValides,
            'questionsInvalides' => $questionsInvalides,
            'pourcentageValide' => $pourcentageValide,
            'dateExport' => new \DateTime(),
        ]);

        return $this->generatePdfResponse($html, 'statistiques_' . $quiz->getTitre());
    }

    /**
     * Exporte la liste des résultats des apprenants en PDF.
     */
    public function exportResultatsApprenants(
        Quiz $quiz,
        Formation $formation,
        array $apprenants,
        string $filtre
    ): Response
    {
        $html = $this->twig->render('pdf/quiz_resultats.html.twig', [
            'quiz' => $quiz,
            'formation' => $formation,
            'apprenants' => $apprenants,
            'filtre' => $filtre,
            'dateExport' => new \DateTime(),
        ]);

        return $this->generatePdfResponse($html, 'resultats_' . $quiz->getTitre());
    }

    /**
     * Génère la réponse HTTP avec le PDF.
     */
    private function generatePdfResponse(string $html, string $filename): Response
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Nettoyer le nom du fichier
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $safeFilename . '.pdf"',
            ]
        );
    }
}

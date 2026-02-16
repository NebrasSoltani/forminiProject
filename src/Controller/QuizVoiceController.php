<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quiz')]
#[IsGranted('ROLE_USER')]
class QuizVoiceController extends AbstractController
{
    /**
     * Endpoint pour tester la reconnaissance vocaleavancée
     *
     * POST /api/quiz/voicerecognize
     *
     * Body JSON:
     * {
     *   "audio": "base64-encoded-audio",
     *   "mimeType": "audiowebm"
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "text": "Texte reconnu",
     *   "confidence": 0.95
     * }
     */
    #[Route('/voice-recognize', name: 'api_quiz_voice_recognize', methods: ['POST'])]
    public function voiceRecognize(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['audio'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Audio data required'
                ], 400);
            }

            $audioData = $data['audio'];
            $mimeType = $data['mimeType'] ?? 'audio/webm';

            // Valider le base64
            if (!$this->isValidBase64($audioData)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid base64 audio'
                ], 400);
            }

            // Pour le moment, retourner une réponse simple
            // En prtégrer Google Cloud, Azure, ou autre service

            return new JsonResponse([
                'success' => true,
                'text' => 'Reconnaissance vocale reçue',
                'confidence' => 0.85,
                'message' => 'L\'audio a été reçu. Intégrez n service de reconnaissance pour le traiter.'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier si une chaîne est en base64 valide
     */
    private function isValidBase64(string $str): bool
    {
        if ((bool)preg_match('/^[a-zA-Z0-9+\/]*={0,2}$/', $str)) {
            $decoded = base64_decode($str, true);
            return $decoded !== false;
        }
        return false;
    }
}

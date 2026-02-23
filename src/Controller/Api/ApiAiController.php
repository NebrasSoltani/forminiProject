<?php

namespace App\Controller\Api;

use App\Entity\Evenement;
use App\Service\AiRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/ai', name: 'api_ai_')]
class ApiAiController extends AbstractController
{
    private $aiService;

    public function __construct(AiRecommendationService $aiService)
    {
        $this->aiService = $aiService;
    }

    #[Route('/recommend-blogs', name: 'recommend_blogs', methods: ['POST'])]
    public function recommendBlogs(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Build event object from request data
            $eventData = $data['event'] ?? [];
            $evenement = new Evenement();
            $evenement->setTitre($eventData['title'] ?? '');
            $evenement->setDescription($eventData['description'] ?? '');
            $evenement->setFilieres($eventData['filieres'] ?? []);

            // Tags comes as a comma-separated string from the frontend input
            $tags = $eventData['tags'] ?? '';
            if (is_string($tags)) {
                $tags = array_filter(array_map('trim', explode(',', $tags)));
            }
            $evenement->setTags($tags);

            $userQuery = $data['query'] ?? null;

            $result = $this->aiService->recommendBlogs($evenement, $userQuery);

            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur interne du serveur: ' . $e->getMessage()], 500);
        }
    }
}


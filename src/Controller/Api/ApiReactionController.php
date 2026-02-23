<?php

namespace App\Controller\Api;

use App\Entity\Evenement;
use App\Entity\LiveReaction;
use App\Repository\EvenementRepository;
use App\Repository\LiveReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/reactions')]
class ApiReactionController extends AbstractController
{
    #[Route('', name: 'api_reaction_send', methods: ['POST'])]
    public function send(Request $request, EvenementRepository $eventRepo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $eventId = $data['eventId'] ?? null;
        $type = $data['type'] ?? null;

        if (!$eventId || !$type) {
            return $this->json(['error' => 'Missing data'], 400);
        }

        $event = $eventRepo->find($eventId);
        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }

        $reaction = new LiveReaction();
        $reaction->setUser($user);
        $reaction->setEvenement($event);
        $reaction->setType($type);

        $em->persist($reaction);
        $em->flush();

        return $this->json([
            'success' => true,
            'userName' => $user->getPrenom() . ' ' . $user->getNom(),
            'type' => $type
        ]);
    }

    #[Route('/event/{id}', name: 'api_reactions_get', methods: ['GET'])]
    public function getReactions(int $id, LiveReactionRepository $reactionRepo): JsonResponse
    {
        $reactions = $reactionRepo->findRecentByEvent($id, 15);
        
        $data = array_map(function($r) {
            return [
                'id' => $r->getId(),
                'userName' => $r->getUser()->getPrenom() . ' ' . $r->getUser()->getNom(),
                'type' => $r->getType(),
                'createdAt' => $r->getCreatedAt()->format('c')
            ];
        }, $reactions);

        return $this->json($data);
    }

    #[Route('/stats/{id}', name: 'api_reactions_stats', methods: ['GET'])]
    public function getStats(int $id, LiveReactionRepository $reactionRepo): JsonResponse
    {
        $count = $reactionRepo->countByEvent($id);
        return $this->json(['total' => $count]);
    }
}

<?php

namespace App\Controller\Api;

use App\Entity\LiveComment;
use App\Repository\EvenementRepository;
use App\Repository\LiveCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/comments')]
class ApiCommentController extends AbstractController
{
    #[Route('', name: 'api_comment_send', methods: ['POST'])]
    public function send(Request $request, EvenementRepository $eventRepo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $eventId = $data['eventId'] ?? null;
        $content = $data['content'] ?? null;

        if (!$eventId || !$content) {
            return $this->json(['error' => 'Missing data'], 400);
        }

        $event = $eventRepo->find($eventId);
        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }

        $comment = new LiveComment();
        $comment->setUser($user);
        $comment->setEvenement($event);
        $comment->setContent($content);

        $em->persist($comment);
        $em->flush();

        return $this->json([
            'success' => true,
            'userName' => $user->getPrenom() . ' ' . $user->getNom(),
            'content' => $content
        ]);
    }

    #[Route('/event/{id}', name: 'api_comments_get', methods: ['GET'])]
    public function getComments(int $id, LiveCommentRepository $commentRepo): JsonResponse
    {
        $comments = $commentRepo->findRecentByEvent($id, 20);
        
        $data = array_map(function($c) {
            return [
                'id' => $c->getId(),
                'userName' => $c->getUser()->getPrenom() . ' ' . $c->getUser()->getNom(),
                'content' => $c->getContent(),
                'createdAt' => $c->getCreatedAt()->format('c')
            ];
        }, $comments);

        return $this->json($data);
    }
}

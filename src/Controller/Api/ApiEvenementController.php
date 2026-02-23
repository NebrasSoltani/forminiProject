<?php

namespace App\Controller\Api;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/evenements')]
class ApiEvenementController extends AbstractController
{
    #[Route('', name: 'api_evenements_list', methods: ['GET'])]
    public function index(Request $request, \Doctrine\ORM\EntityManagerInterface $em): JsonResponse
    {
        $filiere = $request->query->get('filiere');
        $conn = $em->getConnection();

        $sql = 'SELECT id, titre, description, date_debut as date, lieu, filieres, tags FROM evenement WHERE 1=1';
        $params = [];

        if ($filiere) {
            $sql .= ' AND JSON_CONTAINS(filieres, :filiere)';
            // Verify if we need to wrap in quotes for JSON_CONTAINS. 
            // json_encode("string") returns "\"string\"" which is correct for JSON_CONTAINS candidate.
            $params['filiere'] = json_encode($filiere); 
        }

        $resultSet = $conn->executeQuery($sql, $params);
        $events = $resultSet->fetchAllAssociative();

        $data = array_map(function ($event) {
            return [
                'id' => $event['id'],
                'titre' => $event['titre'],
                'description' => $event['description'],
                'date' => $event['date'], // DBAL returns string for datetime usually
                'lieu' => $event['lieu'],
                'filieres' => json_decode($event['filieres'], true), // Manually decode JSON
                'tags' => json_decode($event['tags'], true),
            ];
        }, $events);

        return $this->json($data);
    }

    #[Route('/live-stream', name: 'api_live_stream', methods: ['GET'])]
    public function liveStream(EvenementRepository $repository): JsonResponse
    {
        $liveEvents = $repository->findBy(['live' => true, 'isActif' => true]);

        $data = array_map(function ($event) {
            return [
                'id' => $event->getId(),
                'titre' => $event->getTitre(),
                'streamUrl' => $event->getStreamUrl(),
                'isYouTube' => (bool) preg_match('/(?:youtube\.com|youtu\.be)/', $event->getStreamUrl())
            ];
        }, $liveEvents);

        return $this->json($data);
    }
}

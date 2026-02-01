<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\ResultatQuiz;
use App\Repository\ResultatQuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/resultats-quiz', name: 'api_resultat_quiz_')]
class ResultatQuizController extends AbstractController
{
    public function __construct(
        private readonly ResultatQuizRepository $resultatRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

  
    /**
     * Liste tous les résultats (admin / global)
     * GET /api/resultats-quiz
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $qb = $this->resultatRepository->createQueryBuilder('r')
            ->orderBy('r.dateRealisation', 'DESC');

        // Pagination manuelle simple
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(5, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        $total = (clone $qb)->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();
        $items = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        return $this->json([
            'items' => $items,
            'meta'  => [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ]
        ], Response::HTTP_OK, [], ['groups' => ['resultat:read', 'resultat:quiz']]);
    }

    /**
     * Résultats d'un quiz spécifique
     * GET /api/resultats-quiz/quiz/{quizId}
     */
    #[Route('/quiz/{quizId}', name: 'by_quiz', methods: ['GET'])]
    public function byQuiz(int $quizId, Request $request): JsonResponse
    {
        $quiz = $this->entityManager->getRepository(Quiz::class)->find($quizId);
        if (!$quiz) {
            throw new NotFoundHttpException('Quiz non trouvé');
        }

        $qb = $this->resultatRepository->createQueryBuilder('r')
            ->where('r.quiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->orderBy('r.dateRealisation', 'DESC');

        // Pagination
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(5, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        $total = (clone $qb)->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();
        $items = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        return $this->json([
            'quiz_id' => $quizId,
            'items'   => $items,
            'meta'    => [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ]
        ], Response::HTTP_OK, [], ['groups' => ['resultat:read', 'resultat:quiz']]);
    }

    /**
     * Détail d'un résultat
     * GET /api/resultats-quiz/{id}
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(?ResultatQuiz $resultatQuiz): JsonResponse
    {
        if (!$resultatQuiz) {
            throw new NotFoundHttpException('Résultat non trouvé');
        }

        return $this->json($resultatQuiz, Response::HTTP_OK, [], [
            'groups' => ['resultat:read', 'resultat:quiz', 'resultat:details']
        ]);
    }

    /**
     * Créer un nouveau résultat
     * POST /api/resultats-quiz
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $resultat = $this->serializer->deserialize(
            $request->getContent(),
            ResultatQuiz::class,
            'json'
        );

        if (!$resultat->getQuiz()) {
            return $this->json(['error' => 'Quiz requis'], Response::HTTP_BAD_REQUEST);
        }

        $quiz = $this->entityManager->getRepository(Quiz::class)->find($resultat->getQuiz()->getId());
        if (!$quiz) {
            return $this->json(['error' => 'Quiz invalide'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validator->validate($resultat);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($resultat);
        $this->entityManager->flush();

        return $this->json($resultat, Response::HTTP_CREATED, [], ['groups' => 'resultat:read']);
    }

    /**
     * Mise à jour (PUT/PATCH)
     * PUT/PATCH /api/resultats-quiz/{id}
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, ?ResultatQuiz $resultatQuiz): JsonResponse
    {
        if (!$resultatQuiz) {
            throw new NotFoundHttpException('Résultat non trouvé');
        }

        $this->serializer->deserialize(
            $request->getContent(),
            ResultatQuiz::class,
            'json',
            ['object_to_populate' => $resultatQuiz]
        );

        $errors = $this->validator->validate($resultatQuiz);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($resultatQuiz, Response::HTTP_OK, [], ['groups' => 'resultat:read']);
    }

    /**
     * Suppression
     * DELETE /api/resultats-quiz/{id}
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(?ResultatQuiz $resultatQuiz): JsonResponse
    {
        if (!$resultatQuiz) {
            throw new NotFoundHttpException('Résultat non trouvé');
        }

        $this->entityManager->remove($resultatQuiz);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

 
    /**
     * Les 10 derniers résultats (tous quizzes)
     * GET /api/resultats-quiz/recent
     */
    #[Route('/recent', name: 'recent', methods: ['GET'])]
    public function recent(): JsonResponse
    {
        $resultats = $this->resultatRepository->findBy(
            [],
            ['dateRealisation' => 'DESC'],
            10
        );

        return $this->json($resultats, Response::HTTP_OK, [], ['groups' => ['resultat:read', 'resultat:quiz']]);
    }

    /**
     * Meilleurs scores pour un quiz
 
     */
    #[Route('/quiz/{quizId}/top', name: 'top_scores', methods: ['GET'])]
    public function topScores(int $quizId, Request $request): JsonResponse
    {
        $limit = min(50, max(3, $request->query->getInt('limit', 10)));

        $resultats = $this->resultatRepository->createQueryBuilder('r')
            ->where('r.quiz = :quiz')
            ->setParameter('quiz', $quizId)
            ->orderBy('r.scoreObtenu', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->json([
            'quiz_id' => $quizId,
            'top_scores' => $resultats
        ], Response::HTTP_OK, [], ['groups' => ['resultat:read', 'resultat:quiz']]);
    }

    /**
     * Statistiques rapides d'un quiz

     */
    #[Route('/quiz/{quizId}/stats', name: 'quiz_stats', methods: ['GET'])]
    public function quizStats(int $quizId): JsonResponse
    {
        $quiz = $this->entityManager->getRepository(Quiz::class)->find($quizId);
        if (!$quiz) {
            throw new NotFoundHttpException('Quiz non trouvé');
        }

        $resultats = $this->resultatRepository->findBy(['quiz' => $quizId]);

        $count = count($resultats);
        if ($count === 0) {
            return $this->json([
                'quiz_id' => $quizId,
                'participations' => 0,
                'score_moyen' => null,
                'meilleur_score' => null,
                'note_sur' => $quiz->getNoteSur(),
            ]);
        }

        $scores = array_map(fn($r) => $r->getScoreObtenu(), $resultats);

        $stats = [
            'quiz_id'           => $quizId,
            'participations'    => $count,
            'score_moyen'       => round(array_sum($scores) / $count, 2),
            'meilleur_score'    => max($scores),
            'pire_score'        => min($scores),
            'note_sur'          => $quiz->getNoteSur(),
            'pourcentage_moyen' => $quiz->getNoteSur() ? round((array_sum($scores) / $count / $quiz->getNoteSur()) * 100, 1) : null,
        ];

        return $this->json($stats);
    }


    /**
     * Résultats terminés vs abandonnés (si tu utilises le champ statut)
     * GET /api/resultats-quiz/stats/global
     */
    #[Route('/stats/global', name: 'global_stats', methods: ['GET'])]
    public function globalStats(): JsonResponse
    {
        $counts = $this->resultatRepository->createQueryBuilder('r')
            ->select('r.statut, COUNT(r.id) as count')
            ->groupBy('r.statut')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($counts as $row) {
            $stats[$row['statut']] = (int)$row['count'];
        }

        $stats['total'] = array_sum($stats);

        return $this->json($stats);
    }
}
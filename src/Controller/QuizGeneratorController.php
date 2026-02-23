<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Reponse;
use App\Repository\FormationRepository;
use App\Repository\QuizRepository;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formateur/formation/{formationId}/quiz/{quizId}/generate')]
#[IsGranted('ROLE_USER')]
class QuizGeneratorController extends AbstractController
{
    public function __construct(
        private readonly GeminiService          $geminiService,
        private readonly FormationRepository    $formationRepository,
        private readonly QuizRepository         $quizRepository,
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * Page de génération automatique de questions.
     */
    #[Route('', name: 'quiz_generate_questions', methods: ['GET'])]
    public function index(int $formationId, int $quizId): Response
    {
        [$formation, $quiz] = $this->getEntitiesOrThrow($formationId, $quizId);

        return $this->render('quiz_generator/generate_questions.html.twig', [
            'formation' => $formation,
            'quiz'      => $quiz,
        ]);
    }

    /**
     * Endpoint AJAX : prévisualisation des questions générées (sans sauvegarde).
     */
    #[Route('/preview', name: 'quiz_generate_preview', methods: ['POST'])]
    public function preview(Request $request, int $formationId, int $quizId): JsonResponse
    {
        [$formation, $quiz] = $this->getEntitiesOrThrow($formationId, $quizId);

        $data = json_decode($request->getContent(), true);

        try {
            $questions = $this->geminiService->generateQuestions(
                sujet:        $data['sujet']         ?? $quiz->getTitre(),
                nombre:       (int)($data['nombre']  ?? 5),
                types:        $data['types']          ?? ['qcm'],
                pointsDefaut: (int)($data['points']  ?? 1)
            );

            return $this->json([
                'success'   => true,
                'questions' => $questions,
                'count'     => count($questions),
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint AJAX : sauvegarde les questions sélectionnées en base.
     */
    #[Route('/save', name: 'quiz_generate_save', methods: ['POST'])]
    public function save(Request $request, int $formationId, int $quizId): JsonResponse
    {
        [$formation, $quiz] = $this->getEntitiesOrThrow($formationId, $quizId);

        $data      = json_decode($request->getContent(), true);
        $questions = $data['questions'] ?? [];

        if (empty($questions)) {
            return $this->json(['success' => false, 'error' => 'Aucune question à sauvegarder.'], 400);
        }

        // Récupérer le prochain ordre disponible
        $dernierOrdre = $this->em->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->select('MAX(q.ordre)')
            ->where('q.quiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $savedCount = 0;

        foreach ($questions as $qData) {
            $question = new Question();
            $question->setQuiz($quiz);
            $question->setEnonce($qData['enonce']);
            $question->setType($qData['type']);
            $question->setPoints(max(1, min(100, (int)($qData['points'] ?? 1))));
            $question->setOrdre(++$dernierOrdre);

            if (!empty($qData['explication'])) {
                $question->setExplication($qData['explication']);
            }

            $this->em->persist($question);

            // Ajouter les réponses pour QCM et Vrai/Faux
            foreach ($qData['reponses'] ?? [] as $rData) {
                if (empty(trim($rData['texte'] ?? ''))) {
                    continue;
                }

                $reponse = new Reponse();
                $reponse->setTexte(trim($rData['texte']));
                $reponse->setEstCorrecte((bool)($rData['estCorrecte'] ?? false));
                $reponse->setQuestion($question);

                $this->em->persist($reponse);
            }

            $savedCount++;
        }

        $this->em->flush();

        return $this->json([
            'success'     => true,
            'savedCount'  => $savedCount,
            'redirectUrl' => $this->generateUrl('question_index', [
                'formationId' => $formationId,
                'quizId'      => $quizId,
            ]),
        ]);
    }

    /**
     * Helper : récupère Formation et Quiz en vérifiant les droits.
     */
    private function getEntitiesOrThrow(int $formationId, int $quizId): array
    {
        $formation = $this->formationRepository->find($formationId);
        $quiz      = $this->quizRepository->find($quizId);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        return [$formation, $quiz];
    }
}

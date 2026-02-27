<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Quiz;
use App\Form\QuizType;
use App\Repository\FormationRepository;
use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use App\Repository\ResultatQuizRepository;
use App\Service\QuizPdfExportService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/formateur/formation/{formationId}/quiz')]
#[IsGranted('ROLE_USER')]
class QuizController extends AbstractController
{
    // =========================================================================
    //  BUNDLE 1 — KnpPaginatorBundle : pagination de la liste des quiz
    // =========================================================================

    #[Route('/', name: 'quiz_index', methods: ['GET'])]
    public function index(
        int $formationId,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        PaginatorInterface $paginator,  // ← Bundle KnpPaginator injecté
        Request $request
    ): Response
    {
        $formation = $formationRepository->find($formationId);

        if (!$formation) {
            throw $this->createNotFoundException('Formation non trouvée');
        }

        if ($formation->getFormateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // ── KnpPaginator : on pagine la requête au lieu de tout charger ──
        $queryBuilder = $quizRepository->createQueryBuilder('q')
            ->where('q.formation = :formation')
            ->setParameter('formation', $formation)
            ->orderBy('q.id', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,                          // requête Doctrine
            $request->query->getInt('page', 1),     // numéro de page
            6                                       // nombre d'éléments par page
        );

        return $this->render('quiz/index.html.twig', [
            'formation' => $formation,
            'pagination' => $pagination,  // ← on passe la pagination, pas un tableau
        ]);
    }

    #[Route('/new', name: 'quiz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $formationId, FormationRepository $formationRepository, EntityManagerInterface $em): Response
    {
        $formation = $formationRepository->find($formationId);

        if (!$formation) {
            throw $this->createNotFoundException('Formation non trouvée');
        }

        if ($formation->getFormateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $quiz = new Quiz();
        $quiz->setFormation($formation);

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($quiz);
            $em->flush();

            $this->addFlash('success', 'Quiz créé avec succès !');
            return $this->redirectToRoute('quiz_index', ['formationId' => $formationId]);
        } elseif ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('quiz/new.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'quiz_show', methods: ['GET'])]
    public function show(int $formationId, int $id, FormationRepository $formationRepository, QuizRepository $quizRepository): Response
    {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('quiz/show.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}/edit', name: 'quiz_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $formationId, int $id, FormationRepository $formationRepository, QuizRepository $quizRepository, EntityManagerInterface $em): Response
    {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Quiz modifié avec succès !');
            return $this->redirectToRoute('quiz_index', ['formationId' => $formationId]);
        } elseif ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('quiz/edit.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'quiz_delete', methods: ['POST'])]
    public function delete(Request $request, int $formationId, int $id, FormationRepository $formationRepository, QuizRepository $quizRepository, EntityManagerInterface $em): Response
    {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            $em->remove($quiz);
            $em->flush();

            $this->addFlash('success', 'Quiz supprimé avec succès !');
        }

        return $this->redirectToRoute('quiz_index', ['formationId' => $formationId]);
    }

    // =========================================================================
    //  BUNDLE 2 — Symfony UX Chart.js : graphiques créés côté serveur (PHP)
    // =========================================================================

    #[Route('/{id}/statistiques', name: 'quiz_statistiques', methods: ['GET'])]
    public function statistiques(
        int $formationId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        ChartBuilderInterface $chartBuilder  // ← Bundle Symfony UX Chart.js injecté
    ): Response
    {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        $questions = $quiz->getQuestions();
        $totalQuestions = count($questions);
        $totalReponses = 0;
        $bonnesReponses = 0;
        $questionsValides = 0;

        foreach ($questions as $question) {
            $reponses = $question->getReponses();
            $totalReponses += count($reponses);

            $correctes = 0;
            foreach ($reponses as $reponse) {
                if ($reponse->isEstCorrecte()) {
                    $correctes++;
                }
            }

            if ($correctes > 0) {
                $questionsValides++;
                $bonnesReponses += $correctes;
            }
        }

        $questionsInvalides = $totalQuestions - $questionsValides;
        $pourcentageValide = $totalQuestions > 0 ? round(($questionsValides / $totalQuestions) * 100, 2) : 0;

        // ── Chart.js UX : Graphique 1 — Taux de réussite (doughnut) ──
        $chartReussite = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chartReussite->setData([
            'labels' => ['Réussite', 'Échec'],
            'datasets' => [
                [
                    'data' => [$bonnesReponses, $totalReponses - $bonnesReponses],
                    'backgroundColor' => ['#198754', '#dc3545'],
                    'borderWidth' => 4,
                    'borderColor' => '#ffffff',
                    'hoverOffset' => 24,
                ],
            ],
        ]);
        $taux = $totalReponses > 0 ? round(($bonnesReponses / $totalReponses) * 100, 1) : 0;
        $chartReussite->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '68%',
            'plugins' => [
                'legend' => ['position' => 'bottom', 'labels' => ['font' => ['size' => 14]]],
                'title' => [
                    'display' => true,
                    'text' => $taux . '% de réussite',
                    'font' => ['size' => 24, 'weight' => '600'],
                    'padding' => ['top' => 10, 'bottom' => 20],
                ],
            ],
        ]);

        // ── Chart.js UX : Graphique 2 — Questions valides/invalides (doughnut) ──
        $chartQuestions = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chartQuestions->setData([
            'labels' => ['Valides', 'Invalides'],
            'datasets' => [
                [
                    'data' => [$questionsValides, $questionsInvalides],
                    'backgroundColor' => ['#2e7d32', '#c62828'],
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
                ],
            ],
        ]);
        $chartQuestions->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '62%',
            'plugins' => [
                'legend' => ['position' => 'bottom'],
                'title' => ['display' => true, 'text' => 'État des questions', 'font' => ['size' => 17]],
            ],
        ]);

        // ── Chart.js UX : Graphique 3 — Réponses (bar) ──
        $chartReponses = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chartReponses->setData([
            'labels' => ['Total', 'Bonnes'],
            'datasets' => [
                [
                    'data' => [$totalReponses, $bonnesReponses],
                    'backgroundColor' => ['rgba(33, 150, 243, 0.65)', 'rgba(76, 175, 80, 0.65)'],
                    'borderColor' => ['#2196f3', '#4caf50'],
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                    'categoryPercentage' => 0.55,
                ],
            ],
        ]);
        $chartReponses->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
                'title' => ['display' => true, 'text' => 'Réponses', 'font' => ['size' => 17]],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ]);

        return $this->render('quiz/statistiques.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'totalQuestions' => $totalQuestions,
            'totalReponses' => $totalReponses,
            'bonnesReponses' => $bonnesReponses,
            'questionsValides' => $questionsValides,
            'questionsInvalides' => $questionsInvalides,
            'pourcentageValide' => $pourcentageValide,
            // ── Les 3 charts sont passés au template ──
            'chartReussite' => $chartReussite,
            'chartQuestions' => $chartQuestions,
            'chartReponses' => $chartReponses,
        ]);
    }

    // =========================================================================
    //  BUNDLE 1 — KnpPaginatorBundle : pagination de la liste des apprenants
    // =========================================================================

    #[Route('/{id}/reussite', name: 'quiz_reussite', methods: ['GET'])]
    public function reussite(
        int $formationId,
        int $id,
        Request $request,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        ResultatQuizRepository $resultatQuizRepository,
        PaginatorInterface $paginator  // ← Bundle KnpPaginator injecté
    ): Response
    {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        // Filtre (all, reussi, non-reussi)
        $filtre = $request->query->get('filtre', 'all');
        // Recherche par nom, prénom ou email
        $search = strtolower(trim($request->query->get('search', '')));

        // On récupère uniquement les résultats enregistrés pour ce quiz
        $resultats = $resultatQuizRepository->findBy(['quiz' => $quiz], ['dateTentative' => 'DESC']);
        
        $apprenants = [];
        // Utiliser un tableau pour ne garder que le dernier résultat par apprenant si nécessaire
        $seenApprenants = [];

        foreach ($resultats as $resultat) {
            $user = $resultat->getApprenant();
            if (in_array($user->getId(), $seenApprenants)) continue;
            
            $seenApprenants[] = $user->getId();
            $apprenants[] = [
                'user' => $user,
                'reussi' => $resultat->isReussi(),
                'note' => $resultat->getNote(),
                'date' => $resultat->getDateTentative()
            ];
        }

        // Appliquer le filtre
        if ($filtre === 'reussi') {
            $apprenants = array_filter($apprenants, fn($a) => $a['reussi'] === true);
        } elseif ($filtre === 'non-reussi') {
            $apprenants = array_filter($apprenants, fn($a) => $a['reussi'] === false);
        }

        // Appliquer la recherche
        if ($search !== '') {
            $apprenants = array_filter($apprenants, function($a) use ($search) {
                $user = $a['user'];
                return str_contains(strtolower($user->getNom() ?? ''), $search)
                    || str_contains(strtolower($user->getPrenom() ?? ''), $search)
                    || str_contains(strtolower($user->getEmail() ?? ''), $search);
            });
        }

        // ── KnpPaginator : on pagine le tableau filtré ──
        $pagination = $paginator->paginate(
            array_values($apprenants),               // tableau PHP paginé
            $request->query->getInt('page', 1),      // numéro de page
            10                                        // 10 apprenants par page
        );

        return $this->render('quiz/reussite.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'pagination' => $pagination,   // ← pagination au lieu de tableau brut
            'totalApprenants' => count($apprenants),
            'filtre' => $filtre,
            'search' => $search
        ]);
    }

    // =========================================================================
    //  BUNDLE 3 — Dompdf : export PDF des statistiques du quiz
    // =========================================================================

    #[Route('/{id}/export-statistiques-pdf', name: 'quiz_export_statistiques_pdf', methods: ['GET'])]
    public function exportStatistiquesPdf(
        int $formationId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        QuizPdfExportService $pdfExportService  // ← Service utilisant le bundle Dompdf
    ): Response
    {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        // Calculs statistiques
        $questions = $quiz->getQuestions();
        $totalQuestions = count($questions);
        $totalReponses = 0;
        $bonnesReponses = 0;
        $questionsValides = 0;

        foreach ($questions as $question) {
            $reponses = $question->getReponses();
            $totalReponses += count($reponses);
            $correctes = 0;
            foreach ($reponses as $reponse) {
                if ($reponse->isEstCorrecte()) {
                    $correctes++;
                }
            }
            if ($correctes > 0) {
                $questionsValides++;
                $bonnesReponses += $correctes;
            }
        }

        $questionsInvalides = $totalQuestions - $questionsValides;
        $pourcentageValide = $totalQuestions > 0 ? round(($questionsValides / $totalQuestions) * 100, 2) : 0;

        return $pdfExportService->exportStatistiques(
            $quiz, $formation,
            $totalQuestions, $totalReponses, $bonnesReponses,
            $questionsValides, $questionsInvalides, $pourcentageValide
        );
    }

    // =========================================================================
    //  BUNDLE 3 — Dompdf : export PDF des résultats des apprenants
    // =========================================================================

    #[Route('/{id}/export-resultats-pdf', name: 'quiz_export_resultats_pdf', methods: ['GET'])]
    public function exportResultatsPdf(
        int $formationId,
        int $id,
        Request $request,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        ResultatQuizRepository $resultatQuizRepository,
        QuizPdfExportService $pdfExportService  // ← Service utilisant le bundle Dompdf
    ): Response
    {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        $filtre = $request->query->get('filtre', 'all');
        
        // Uniquement les résultats enregistrés pour ce quiz
        $resultats = $resultatQuizRepository->findBy(['quiz' => $quiz], ['dateTentative' => 'DESC']);
        $apprenants = [];
        $seenApprenants = [];

        foreach ($resultats as $resultat) {
            $user = $resultat->getApprenant();
            if (in_array($user->getId(), $seenApprenants)) continue;
            
            $seenApprenants[] = $user->getId();
            $apprenants[] = [
                'user' => $user,
                'reussi' => $resultat->isReussi()
            ];
        }

        if ($filtre === 'reussi') {
            $apprenants = array_filter($apprenants, fn($a) => $a['reussi'] === true);
        } elseif ($filtre === 'non-reussi') {
            $apprenants = array_filter($apprenants, fn($a) => $a['reussi'] === false);
        }

        return $pdfExportService->exportResultatsApprenants($quiz, $formation, array_values($apprenants), $filtre);
    }
}

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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formateur/formation/{formationId}/quiz')]
#[IsGranted('ROLE_USER')]
class QuizController extends AbstractController
{
    #[Route('/', name: 'quiz_index', methods: ['GET'])]
    public function index(int $formationId, FormationRepository $formationRepository, QuizRepository $quizRepository): Response
    {
        $formation = $formationRepository->find($formationId);

        if (!$formation) {
            throw $this->createNotFoundException('Formation non trouvée');
        }

        if ($formation->getFormateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $quizzes = $quizRepository->findByFormation($formationId);

        return $this->render('quiz/index.html.twig', [
            'formation' => $formation,
            'quizzes' => $quizzes,
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
     #[Route('/{id}/statistiques', name: 'quiz_statistiques', methods: ['GET'])]
    public function statistiques(
        int $formationId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository
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
                // ✅ Utiliser isEstCorrecte() pour les booléens
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

        return $this->render('quiz/statistiques.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'totalQuestions' => $totalQuestions,
            'totalReponses' => $totalReponses,
            'bonnesReponses' => $bonnesReponses,
            'questionsValides' => $questionsValides,
            'questionsInvalides' => $questionsInvalides,
            'pourcentageValide' => $pourcentageValide,
        ]);
    }
#[Route('/{id}/reussite', name: 'quiz_reussite', methods: ['GET'])]
public function reussite(
    int $formationId,
    int $id,
    Request $request,
    FormationRepository $formationRepository,
    QuizRepository $quizRepository,
    ResultatQuizRepository $resultatQuizRepository
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

    $resultats = $resultatQuizRepository->findBy(['quiz' => $quiz]);

    $apprenants = [];
    foreach ($resultats as $resultat) {
        $apprenants[] = [
            'user' => $resultat->getApprenant(),
            'reussi' => $resultat->isReussi()
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
            return str_contains(strtolower($user->getNom()), $search)
                || str_contains(strtolower($user->getPrenom()), $search)
                || str_contains(strtolower($user->getEmail()), $search);
        });
    }

    return $this->render('quiz/reussite.html.twig', [
        'formation' => $formation,
        'quiz' => $quiz,
        'apprenants' => $apprenants,
        'filtre' => $filtre,
        'search' => $search
    ]);
}



}

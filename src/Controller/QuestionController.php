<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\Reponse;
use App\Form\QuestionType;
use App\Repository\FormationRepository;
use App\Repository\QuestionRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formateur/formation/{formationId}/quiz/{quizId}/question')]
#[IsGranted('ROLE_USER')]
class QuestionController extends AbstractController
{
    #[Route('/', name: 'question_index', methods: ['GET'])]
    public function index(
        int $formationId,
        int $quizId,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($quizId);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        $questions = $questionRepository->findByQuizOrdered($quizId);

        return $this->render('question/index.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    #[Route('/new', name: 'question_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        int $formationId,
        int $quizId,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($quizId);

        if (!$formation || !$quiz) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $quiz->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        $question = new Question();
        $question->setQuiz($quiz);

        // Définir l'ordre automatiquement
        $dernierOrdre = $em->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->select('MAX(q.ordre)')
            ->where('q.quiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->getQuery()
            ->getSingleScalarResult();

        $question->setOrdre(($dernierOrdre ?? 0) + 1);

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($question);
            $em->flush();

            $this->addFlash('success', 'Question créée avec succès !');
            return $this->redirectToRoute('question_edit', [
                'formationId' => $formationId,
                'quizId' => $quizId,
                'id' => $question->getId()
            ]);
        }

        return $this->render('question/new.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'question_show', methods: ['GET'])]
    public function show(
        int $formationId,
        int $quizId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($quizId);
        $question = $questionRepository->find($id);

        if (!$formation || !$quiz || !$question) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() ||
            $quiz->getFormation() !== $formation ||
            $question->getQuiz() !== $quiz) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('question/show.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'question' => $question,
        ]);
    }

    #[Route('/{id}/edit', name: 'question_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        int $formationId,
        int $quizId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($quizId);
        $question = $questionRepository->find($id);

        if (!$formation || !$quiz || !$question) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() ||
            $quiz->getFormation() !== $formation ||
            $question->getQuiz() !== $quiz) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Question modifiée avec succès !');
            return $this->redirectToRoute('question_index', [
                'formationId' => $formationId,
                'quizId' => $quizId
            ]);
        }

        return $this->render('question/edit.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'question_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        int $formationId,
        int $quizId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($quizId);
        $question = $questionRepository->find($id);

        if (!$formation || !$quiz || !$question) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() ||
            $quiz->getFormation() !== $formation ||
            $question->getQuiz() !== $quiz) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->request->get('_token'))) {
            $em->remove($question);
            $em->flush();
            $this->addFlash('success', 'Question supprimée avec succès !');
        }

        return $this->redirectToRoute('question_index', [
            'formationId' => $formationId,
            'quizId' => $quizId
        ]);
    }

    #[Route('/{id}/reponse/add', name: 'question_add_reponse', methods: ['POST'])]
    public function addReponse(
        Request $request,
        int $formationId,
        int $quizId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($quizId);
        $question = $questionRepository->find($id);

        if (!$formation || !$quiz || !$question) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() ||
            $quiz->getFormation() !== $formation ||
            $question->getQuiz() !== $quiz) {
            throw $this->createAccessDeniedException();
        }

        $texte = $request->request->get('texte');
        $estCorrecte = $request->request->get('estCorrecte') === '1';

        if ($texte) {
            $reponse = new Reponse();
            $reponse->setTexte($texte);
            $reponse->setEstCorrecte($estCorrecte);
            $reponse->setQuestion($question);

            $em->persist($reponse);
            $em->flush();

            $this->addFlash('success', 'Réponse ajoutée avec succès !');
        }

        return $this->redirectToRoute('question_edit', [
            'formationId' => $formationId,
            'quizId' => $quizId,
            'id' => $id
        ]);
    }

    #[Route('/{questionId}/reponse/{reponseId}/delete', name: 'question_delete_reponse', methods: ['POST'])]
    public function deleteReponse(
        Request $request,
        int $formationId,
        int $quizId,
        int $questionId,
        int $reponseId,
        EntityManagerInterface $em
    ): Response {
        $reponse = $em->getRepository(Reponse::class)->find($reponseId);

        if (!$reponse || $reponse->getQuestion()->getId() !== $questionId) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $em->remove($reponse);
            $em->flush();
            $this->addFlash('success', 'Réponse supprimée avec succès !');
        }

        return $this->redirectToRoute('question_edit', [
            'formationId' => $formationId,
            'quizId' => $quizId,
            'id' => $questionId
        ]);
    }
    
}

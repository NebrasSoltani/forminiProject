<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Form\QuizType;
use App\Repository\FormationRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formateur/formation/{formationId}/quiz')]
#[IsGranted('ROLE_FORMATEUR')]
class QuizController extends AbstractController
{
    #[Route('/', name: 'quiz_index', methods: ['GET'])]
    public function index(
        int $formationId,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository
    ): Response {
        $formation = $formationRepository->find($formationId);

        if (!$formation) {
            throw $this->createNotFoundException('Formation non trouvée');
        }

        $this->denyAccessUnlessGranted('edit', $formation); 

        $quizzes = $quizRepository->findBy(['formation' => $formation], ['id' => 'DESC']);

        return $this->render('quiz/index.html.twig', [
            'formation' => $formation,
            'quizzes' => $quizzes,
        ]);
    }

    #[Route('/new', name: 'quiz_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        int $formationId,
        FormationRepository $formationRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($formationId);

        if (!$formation) {
            throw $this->createNotFoundException('Formation non trouvée');
        }

        $this->denyAccessUnlessGranted('edit', $formation);

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
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'quiz_show', methods: ['GET'])]
    public function show(
        int $formationId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz ou formation introuvable');
        }

        $this->denyAccessUnlessGranted('view', $quiz);

        return $this->render('quiz/show.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}/edit', name: 'quiz_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        int $formationId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz ou formation introuvable');
        }

        $this->denyAccessUnlessGranted('edit', $quiz);

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
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'quiz_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        int $formationId,
        int $id,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($id);

        if (!$formation || !$quiz || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz ou formation introuvable');
        }

        $this->denyAccessUnlessGranted('delete', $quiz);

        if ($this->isCsrfTokenValid('delete' . $quiz->getId(), $request->request->get('_token'))) {
            $em->remove($quiz);
            $em->flush();

            $this->addFlash('success', 'Quiz supprimé avec succès !');
        }

        return $this->redirectToRoute('quiz_index', ['formationId' => $formationId]);
    }
}

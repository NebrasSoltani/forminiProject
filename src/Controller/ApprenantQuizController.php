<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Quiz;
use App\Entity\ResultatQuiz;
use App\Entity\ProgressionLecon;
use App\Repository\FormationRepository;
use App\Repository\QuizRepository;
use App\Repository\InscriptionRepository;
use App\Repository\ProgressionLeconRepository;
use App\Repository\ResultatQuizRepository;
use App\Repository\LeconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/apprenant/formation/{formationId}/quiz')]
#[IsGranted('ROLE_USER')]
class ApprenantQuizController extends AbstractController
{
    #[Route('/', name: 'apprenant_quiz_index', methods: ['GET'])]
    public function index(
        int $formationId,
        FormationRepository $formationRepository,
        InscriptionRepository $inscriptionRepository,
        QuizRepository $quizRepository,
        ProgressionLeconRepository $progressionLeconRepository,
        LeconRepository $leconRepository
    ): Response {
          // Vérifier si la formation existe
        $formation = $formationRepository->find($formationId);

        if (!$formation) {
            throw $this->createNotFoundException('Formation non trouvée');
        }

        // Vérifier que l'utilisateur est inscrit à cette formation.
        $inscription = $inscriptionRepository->findOneByApprenantAndFormation($this->getUser(), $formationId);
        if (!$inscription) {
            $this->addFlash('error', 'Vous devez être inscrit à cette formation pour passer les quiz.');
            return $this->redirectToRoute('apprenant_formation_show', ['id' => $formationId]);
        }

        // Récupérer les quiz de la formation
        $quizzes = $quizRepository->findBy(['formation' => $formation]);

        // Vérifier combien de leçons sont terminées
        $totalLecons = $leconRepository->countByFormation($formationId);
        $leconsTerminees = $progressionLeconRepository->countLeconTermineesParFormation($this->getUser(), $formationId);
        $toutesLeconsTerminees = $totalLecons > 0 && $leconsTerminees >= $totalLecons;

        //Rend la vue Twig index.html.twig avec toutes les données nécessaires pour l’affichage.
        return $this->render('apprenant/quiz/index.html.twig', [
            'formation' => $formation,
            'inscription' => $inscription,
            'quizzes' => $quizzes,
            'toutesLeconsTerminees' => $toutesLeconsTerminees,
            'leconsTerminees' => $leconsTerminees,
            'totalLecons' => $totalLecons,
        ]);
    }

    #[Route('/{quizId}/passer', name: 'apprenant_quiz_passer', methods: ['GET'])]
    public function passer(
        int $formationId,
        int $quizId,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        InscriptionRepository $inscriptionRepository,
        ProgressionLeconRepository $progressionLeconRepository,
        LeconRepository $leconRepository
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($quizId);

        if (!$formation || !$quiz || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz non trouvé');
        }

        // Vérifier que l'utilisateur est inscrit
        $inscription = $inscriptionRepository->findOneByApprenantAndFormation($this->getUser(), $formationId);
        if (!$inscription) {
            $this->addFlash('error', 'Vous devez être inscrit à cette formation.');
            return $this->redirectToRoute('apprenant_formation_show', ['id' => $formationId]);
        }

        // Vérifier que toutes les leçons sont terminées
        $totalLecons = $leconRepository->countByFormation($formationId);
        $toutesLeconsTerminees = $progressionLeconRepository->toutesLeconsTerminees($this->getUser(), $formationId, $totalLecons);

        if (!$toutesLeconsTerminees) {
            $this->addFlash('warning', 'Vous devez terminer toutes les leçons avant de passer ce quiz.');
            return $this->redirectToRoute('apprenant_quiz_index', ['formationId' => $formationId]);
        }

        // Récupère les questions du quiz.
        //Mélanger les questions si nécessaire
        $questions = $quiz->getQuestions()->toArray();
        if ($quiz->isMelanger()) {
            shuffle($questions);
        }

        return $this->render('apprenant/quiz/passer.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    //Route POST pour soumettre les réponses au quiz.

    #[Route('/{quizId}/soumettre', name: 'apprenant_quiz_soumettre', methods: ['POST'])]
    public function soumettre(
        Request $request, //contient toutes les données envoyées par l’utilisateur
        int $formationId,
        int $quizId,
        FormationRepository $formationRepository,//permet de récupérer les données de la formation
        QuizRepository $quizRepository,//permet de récupérer les données du quiz
        InscriptionRepository $inscriptionRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($quizId);

        if (!$formation || !$quiz || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz non trouvé');
        }

        // Vérifier que l'utilisateur est inscrit
        $inscription = $inscriptionRepository->findOneByApprenantAndFormation($this->getUser(), $formationId);//permet de vérifier que l’apprenant est bien inscrit à la formation avant de soumettre les réponses au quiz
        if (!$inscription) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer les réponses
        $reponses = $request->request->all('reponses');

        // Calculer le score
        $nombreBonnesReponses = 0;
        $detailsReponses = [];//pour stocker les détails de chaque réponse (question, réponse utilisateur, bonne réponse, etc.)
        $questions = $quiz->getQuestions();//récupère les questions du quiz pour pouvoir les comparer avec les réponses de l’utilisateur

        foreach ($questions as $question) {
            $questionId = $question->getId();
            $reponseUtilisateur = $reponses[$questionId] ?? null;//récupère la réponse de l’utilisateur pour cette question

            // Trouver la bonne réponse
            $bonneReponse = null;
            foreach ($question->getReponses() as $reponse) {//parcourt les réponses de la question pour trouver celle qui est correcte
                if ($reponse->isEstCorrecte()) {
                    $bonneReponse = $reponse;
                    break;
                }
            }

            $estCorrecte = $bonneReponse && $reponseUtilisateur == $bonneReponse->getId();//compare la réponse de l’utilisateur avec la bonne réponse pour déterminer si elle est correcte
            if ($estCorrecte) {
                $nombreBonnesReponses++;
            }

            $detailsReponses[] = [//stocke les détails de la réponse pour pouvoir les afficher dans le résultat du quiz
                'question_id' => $questionId,
                'reponse_utilisateur' => $reponseUtilisateur,
                'reponse_correcte' => $bonneReponse ? $bonneReponse->getId() : null,
                'correct' => $estCorrecte,//indique si la réponse de l’utilisateur est correcte ou non
            ];
        }

        // Calculer la note en pourcentage
        $nombreTotalQuestions = count($questions);
        $note = $nombreTotalQuestions > 0 ? ($nombreBonnesReponses / $nombreTotalQuestions) * 100 : 0;
        $reussi = $note >= $quiz->getNoteMinimale();

        // Enregistrer le résultat
        $resultat = new ResultatQuiz();
        $resultat->setApprenant($this->getUser());
        $resultat->setQuiz($quiz);
        $resultat->setNote(number_format($note, 2));
        $resultat->setNombreBonnesReponses($nombreBonnesReponses);
        $resultat->setNombreTotalQuestions($nombreTotalQuestions);
        $resultat->setReussi($reussi);
        $resultat->setDetailsReponses(json_encode($detailsReponses));

        $em->persist($resultat);
        $em->flush();

        return $this->redirectToRoute('apprenant_quiz_resultat', [
            'formationId' => $formationId,
            'quizId' => $quizId,
            'resultatId' => $resultat->getId(),
        ]);
    }

    #[Route('/{quizId}/resultat/{resultatId}', name: 'apprenant_quiz_resultat', methods: ['GET'])]
    public function resultat(
        int $formationId,
        int $quizId,
        int $resultatId,
        FormationRepository $formationRepository,
        QuizRepository $quizRepository,
        ResultatQuizRepository $resultatQuizRepository
    ): Response {
        $formation = $formationRepository->find($formationId);
        $quiz = $quizRepository->find($quizId);
        $resultat = $resultatQuizRepository->find($resultatId);

        if (!$formation || !$quiz || !$resultat || $resultat->getApprenant() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        // Décoder les détails des réponses
        $detailsReponses = json_decode($resultat->getDetailsReponses(), true);//permet de décoder les détails des réponses qui ont été stockés en JSON pour pouvoir les afficher dans la vue Twig du résultat du quiz

        return $this->render('apprenant/quiz/resultat.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'resultat' => $resultat,
            'detailsReponses' => $detailsReponses,
        ]);
    }
}

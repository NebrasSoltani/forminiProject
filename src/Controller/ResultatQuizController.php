<?php

namespace App\Controller;

use App\Entity\ResultatQuiz;
use App\Repository\ResultatQuizRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/resultats', name: 'resultat_')]
class ResultatQuizController extends AbstractController
{
    private ResultatQuizRepository $repository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $em;
    private QuizRepository $quizRepository;
    private ValidatorInterface $validator;

    public function __construct(
        ResultatQuizRepository $repository,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        QuizRepository $quizRepository,
        ValidatorInterface $validator
    ) {
        $this->repository = $repository;
        $this->serializer = $serializer;
        $this->em = $em;
        $this->quizRepository = $quizRepository;
        $this->validator = $validator;
    }

    /**
     * Lister tous les résultats
     */
  #[Route('', name: 'list', methods: ['GET'])]
public function list(): JsonResponse
{
    $resultats = $this->repository->findBy([], ['dateRealisation' => 'DESC']);

    if (!$resultats) {
        return new JsonResponse(['message' => 'Aucun résultat trouvé.'], JsonResponse::HTTP_NOT_FOUND);
    }

    $data = $this->serializer->serialize($resultats, 'json', ['groups' => 'resultat:read']);
    return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
}

    /**
     * Créer un nouveau résultat
     */
#[Route('', name: 'create', methods: ['POST'])]
public function create(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!$data) {
        return new JsonResponse(['message' => 'Données invalides'], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Vérifier l'existence du quiz
    $quiz = $this->quizRepository->find($data['quiz_id'] ?? null);
    if (!$quiz) {
        return new JsonResponse(['message' => 'Quiz introuvable'], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Créer le résultat
    $resultat = new ResultatQuiz();
    $resultat->setQuiz($quiz);
    $resultat->setScoreObtenu((float)($data['scoreObtenu'] ?? 0));
    $resultat->setNbQuestionsRepondues((int)($data['nbQuestionsRepondues'] ?? 0));
    $resultat->setTempsPrisSecondes(isset($data['tempsPrisSecondes']) ? (int)$data['tempsPrisSecondes'] : null);
    $resultat->setStatut($data['statut'] ?? 'termine');
    $resultat->setReponsesDetaillees($data['reponsesDetaillees'] ?? null);

    // Validation
    $errors = $this->validator->validate($resultat);
    if (count($errors) > 0) {
        return new JsonResponse(['message' => (string)$errors], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Sauvegarder en base
    $this->em->persist($resultat);
    $this->em->flush();

    // Retourner un message de succès + l'ID du résultat ajouté
    return new JsonResponse([
        'message' => 'Résultat ajouté avec succès',
        'id' => $resultat->getId()
    ], JsonResponse::HTTP_CREATED);
}
#[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
public function update(int $id, Request $request): JsonResponse
{
    // Chercher le résultat existant
    $resultat = $this->repository->find($id);
    if (!$resultat) {
        return new JsonResponse(['message' => 'Résultat introuvable'], JsonResponse::HTTP_NOT_FOUND);
    }

    $data = json_decode($request->getContent(), true);
    if (!$data) {
        return new JsonResponse(['message' => 'Données invalides'], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Mettre à jour les champs si fournis
    if (isset($data['scoreObtenu'])) {
        $resultat->setScoreObtenu((float)$data['scoreObtenu']);
    }
    if (isset($data['nbQuestionsRepondues'])) {
        $resultat->setNbQuestionsRepondues((int)$data['nbQuestionsRepondues']);
    }
    if (isset($data['tempsPrisSecondes'])) {
        $resultat->setTempsPrisSecondes((int)$data['tempsPrisSecondes']);
    }
    if (isset($data['statut'])) {
        $resultat->setStatut($data['statut']);
    }
    if (isset($data['reponsesDetaillees'])) {
        $resultat->setReponsesDetaillees($data['reponsesDetaillees']);
    }

    // Validation
    $errors = $this->validator->validate($resultat);
    if (count($errors) > 0) {
        return new JsonResponse(['message' => (string)$errors], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Sauvegarder les modifications
    $this->em->flush();

    // Réponse de succès
    return new JsonResponse([
        'message' => 'Résultat mis à jour avec succès',
        'id' => $resultat->getId()
    ], JsonResponse::HTTP_OK);
}
#[Route('/{id}', name: 'delete', methods: ['DELETE'])]
public function delete(int $id): JsonResponse
{
    // Chercher le résultat existant
    $resultat = $this->repository->find($id);
    if (!$resultat) {
        return new JsonResponse(['message' => 'Résultat introuvable'], JsonResponse::HTTP_NOT_FOUND);
    }

    // Supprimer le résultat
    $this->em->remove($resultat);
    $this->em->flush();

    // Réponse de succès
    return new JsonResponse(['message' => 'Résultat supprimé avec succès'], JsonResponse::HTTP_OK);
}
#[Route('/recent', name: 'recent', methods: ['GET'])]
public function recent(): JsonResponse
{
    // Récupérer les 10 derniers résultats triés par dateRealisation décroissante
    $resultats = $this->repository->findBy([], ['dateRealisation' => 'DESC'], 10);

    if (!$resultats) {
        return new JsonResponse(['message' => 'Aucun résultat trouvé'], JsonResponse::HTTP_NOT_FOUND);
    }

    // Sérialisation avec le groupe resultat:read
    $data = $this->serializer->serialize($resultats, 'json', ['groups' => 'resultat:read']);

    return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
}
#[Route('/top-scores/{quizId}', name: 'top_scores', methods: ['GET'])]
public function topScores(int $quizId): JsonResponse
{
    // Vérifier si le quiz existe
    $quiz = $this->quizRepository->find($quizId);
    if (!$quiz) {
        return new JsonResponse(['message' => 'Quiz introuvable'], JsonResponse::HTTP_NOT_FOUND);
    }

    // Récupérer les 10 meilleurs scores pour ce quiz
    $resultats = $this->repository->findBy(
        ['quiz' => $quiz],
        ['scoreObtenu' => 'DESC'],
        10
    );

    if (!$resultats) {
        return new JsonResponse(['message' => 'Aucun résultat trouvé pour ce quiz'], JsonResponse::HTTP_NOT_FOUND);
    }

    // Sérialisation avec le groupe resultat:read
    $data = $this->serializer->serialize($resultats, 'json', ['groups' => 'resultat:read']);

    return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
}
#[Route('/stats/global', name: 'global_stats', methods: ['GET'])]
public function globalStats(): JsonResponse
{
    // Compter les résultats terminés
    $termines = $this->repository->count(['statut' => 'termine']);

    // Compter les résultats abandonnés
    $abandons = $this->repository->count(['statut' => 'abandon']);

    // Retourner les statistiques
    return new JsonResponse([
        'termines' => $termines,
        'abandons' => $abandons,
        'total' => $termines + $abandons
    ], JsonResponse::HTTP_OK);
}

}
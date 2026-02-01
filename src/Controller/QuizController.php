<?php

namespace App\Controller;

use App\Entity\Quiz;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/quizzes', name: 'api_quiz_')]
class QuizController extends AbstractController
{
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->getContent();

        try {
            $quiz = $this->serializer->deserialize($data, Quiz::class, 'json');
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'JSON invalide : ' . $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Validation
        $errors = $this->validator->validate($quiz);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse([
                'status' => 'error',
                'errors' => $errorMessages
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Persist
        $this->em->persist($quiz);
        $this->em->flush();

        // Retour JSON avec groupe
        $quizData = $this->serializer->serialize($quiz, 'json', ['groups' => ['quiz']]);

        return new JsonResponse($quizData, JsonResponse::HTTP_CREATED, [], true);
    }
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
public function update(int $id, Request $request): JsonResponse
{
    $quiz = $this->em->getRepository(Quiz::class)->find($id);

    if (!$quiz) {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Quiz non trouvé'
        ], JsonResponse::HTTP_NOT_FOUND);
    }

    try {
        // OBJECT_TO_POPULATE = très important pour update
        $this->serializer->deserialize(
            $request->getContent(),
            Quiz::class,
            'json',
            ['object_to_populate' => $quiz]
        );
    } catch (\Exception $e) {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'JSON invalide : ' . $e->getMessage()
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Validation
    $errors = $this->validator->validate($quiz);
    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }

        return new JsonResponse([
            'status' => 'error',
            'errors' => $errorMessages
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    $this->em->flush();

    $quizData = $this->serializer->serialize($quiz, 'json', ['groups' => ['quiz']]);

    return new JsonResponse($quizData, JsonResponse::HTTP_OK, [], true);
}
#[Route('', name: 'list', methods: ['GET'])]
public function list(): JsonResponse
{
    $quizzes = $this->em->getRepository(Quiz::class)->findAll();

    $data = $this->serializer->serialize(
        $quizzes,
        'json',
        ['groups' => ['quiz']]
    );

    return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
}
#[Route('/{id}', name: 'delete', methods: ['DELETE'])]
public function delete(int $id): JsonResponse
{
    $quiz = $this->em->getRepository(Quiz::class)->find($id);

    if (!$quiz) {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Quiz non trouvé'
        ], JsonResponse::HTTP_NOT_FOUND);
    }

    $this->em->remove($quiz);
    $this->em->flush();

    return new JsonResponse([
        'status' => 'success',
        'message' => 'Quiz supprimé avec succès'
    ], JsonResponse::HTTP_OK);
}
  #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $keyword = $request->query->get('q', '');
        
        if (!$keyword) {
            return $this->json(['error' => 'Paramètre q manquant'], 400);
        }

        // Nettoyage du mot clé (sans accents et en minuscule)
        $slugger = new AsciiSlugger();
        $cleanKeyword = $slugger->slug($keyword)->lower()->toString();

        $quizzes = $this->em->getRepository(Quiz::class)
            ->createQueryBuilder('q')
            ->where('LOWER(q.titre) LIKE :keyword')
            ->setParameter('keyword', '%' . $cleanKeyword . '%')
            ->getQuery()
            ->getResult();

        return $this->json($quizzes, 200, [], ['groups' => 'quiz']);
    }

    // Restriction: id doit être un nombre (\d+)
    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $quiz = $this->em->getRepository(Quiz::class)->find($id);

        if (!$quiz) {
            return $this->json([
                'status' => 'error',
                'message' => 'Quiz non trouvé'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $quizData = $this->serializer->serialize(
            $quiz,
            'json',
            ['groups' => ['quiz']]
        );

        return new JsonResponse($quizData, JsonResponse::HTTP_OK, [], true);
    }
}
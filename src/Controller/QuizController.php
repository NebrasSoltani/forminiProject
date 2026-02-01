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
}

<?php

namespace App\Controller;

use App\Entity\FaceData;
use App\Entity\User;
use App\Service\FaceRecognitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Turbo\Turbo;

#[Route('/face-recognition')]
#[Turbo(disable: true)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class FaceRecognitionController extends AbstractController
{
    private FaceRecognitionService $faceRecognitionService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        FaceRecognitionService $faceRecognitionService,
        EntityManagerInterface $entityManager
    ) {
        $this->faceRecognitionService = $faceRecognitionService;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_face_recognition_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $hasFaceData = $this->faceRecognitionService->hasUserFaceData($user);
        $faceData = $this->faceRecognitionService->getUserFaceData($user);

        return $this->render('face_recognition/index.html.twig', [
            'hasFaceData' => $hasFaceData,
            'faceData' => $faceData,
        ]);
    }

    #[Route('/register', name: 'app_face_recognition_register', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function register(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $file = $request->files->get('face_image');
            
            if (!$file) {
                $this->addFlash('error', 'Please upload an image');
                return $this->redirectToRoute('app_face_recognition_register');
            }

            // Validate file
            if (!in_array($file->guessExtension(), ['jpg', 'jpeg', 'png'])) {
                $this->addFlash('error', 'Please upload a valid image file (JPG, JPEG, PNG)');
                return $this->redirectToRoute('app_face_recognition_register');
            }

            // Process face image
            $result = $this->faceRecognitionService->processFaceImage($file, $user);

            if ($result['success']) {
                $this->addFlash('success', 'Face registered successfully!');
                return $this->redirectToRoute('app_face_recognition_index');
            } else {
                $this->addFlash('error', $result['message']);
            }
        }

        return $this->render('face_recognition/register.html.twig');
    }

    #[Route('/recognize', name: 'app_face_recognition_recognize', methods: ['GET', 'POST'])]
    public function recognize(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('face_image');
            
            if (!$file) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'message' => 'Please upload an image']);
                } else {
                    $this->addFlash('error', 'Please upload an image');
                    return $this->redirectToRoute('app_face_recognition_recognize');
                }
            }

            // Validate file
            if (!in_array($file->guessExtension(), ['jpg', 'jpeg', 'png'])) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'message' => 'Please upload a valid image file (JPG, JPEG, PNG)']);
                } else {
                    $this->addFlash('error', 'Please upload a valid image file (JPG, JPEG, PNG)');
                    return $this->redirectToRoute('app_face_recognition_recognize');
                }
            }

            // Recognize face
            $result = $this->faceRecognitionService->recognizeFace($file);
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse($result);
            } else {
                if ($result['success']) {
                    $this->addFlash('success', 'Face recognized successfully!');
                } else {
                    $this->addFlash('error', $result['message']);
                }
                return $this->redirectToRoute('app_face_recognition_recognize');
            }
        }

        return $this->render('face_recognition/recognize.html.twig');
    }

    #[Route('/delete/{id}', name: 'app_face_recognition_delete', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(FaceData $faceData): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($faceData->getUser() !== $user) {
            throw $this->createAccessDeniedException('You can only delete your own face data');
        }

        $this->entityManager->remove($faceData);
        $this->entityManager->flush();

        $this->addFlash('success', 'Face data deleted successfully');
        return $this->redirectToRoute('app_face_recognition_index');
    }

    #[Route('/api/register', name: 'app_face_recognition_api_register', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function apiRegister(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $file = $request->files->get('face_image');
        
        if (!$file) {
            return new JsonResponse(['success' => false, 'message' => 'Please upload an image']);
        }

        // Validate file
        if (!in_array($file->guessExtension(), ['jpg', 'jpeg', 'png'])) {
            return new JsonResponse(['success' => false, 'message' => 'Please upload a valid image file (JPG, JPEG, PNG)']);
        }

        // Process face image
        $result = $this->faceRecognitionService->processFaceImage($file, $user);
        return new JsonResponse($result);
    }

    #[Route('/api/recognize', name: 'app_face_recognition_api_recognize', methods: ['POST'])]
    public function apiRecognize(Request $request): JsonResponse
    {
        $file = $request->files->get('face_image');
        
        if (!$file) {
            return new JsonResponse(['success' => false, 'message' => 'Please upload an image']);
        }

        // Validate file
        if (!in_array($file->guessExtension(), ['jpg', 'jpeg', 'png'])) {
            return new JsonResponse(['success' => false, 'message' => 'Please upload a valid image file (JPG, JPEG, PNG)']);
        }

        // Recognize face
        $result = $this->faceRecognitionService->recognizeFace($file);
        return new JsonResponse($result);
    }
}

<?php

namespace App\Controller;

use App\Service\FaceApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/face-api')]
class FaceApiController extends AbstractController
{
    private FaceApiService $faceApiService;

    public function __construct(FaceApiService $faceApiService)
    {
        $this->faceApiService = $faceApiService;
    }

    /**
     * Face API demo page
     */
    #[Route('/', name: 'face_api_index')]
    public function index(): Response
    {
        return $this->render('face_api/index.html.twig', [
            'config' => $this->faceApiService->getFaceApiConfig()
        ]);
    }

    /**
     * Upload image for face detection
     */
    #[Route('/upload', name: 'face_api_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        
        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        // Validate file
        $errors = $this->faceApiService->validateImage($file);
        if (!empty($errors)) {
            return new JsonResponse(['error' => implode(', ', $errors)], 400);
        }

        try {
            $imagePath = $this->faceApiService->uploadImage($file);
            return new JsonResponse([
                'success' => true,
                'image_path' => $imagePath,
                'full_url' => $request->getSchemeAndHttpHost() . $imagePath
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to upload image: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process face detection results
     */
    #[Route('/detect', name: 'face_api_detect', methods: ['POST'])]
    public function detect(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }

        try {
            $results = $this->faceApiService->processFaceDetectionResults($data);
            return new JsonResponse([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to process detection: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Face API webcam demo
     */
    #[Route('/webcam', name: 'face_api_webcam')]
    public function webcam(): Response
    {
        return $this->render('face_api/webcam.html.twig', [
            'config' => $this->faceApiService->getFaceApiConfig()
        ]);
    }

    /**
     * Face API configuration endpoint
     */
    #[Route('/config', name: 'face_api_config')]
    public function config(): JsonResponse
    {
        return new JsonResponse([
            'config' => $this->faceApiService->getFaceApiConfig()
        ]);
    }

    /**
     * Clean up uploaded files
     */
    #[Route('/cleanup/{filename}', name: 'face_api_cleanup')]
    public function cleanup(string $filename): JsonResponse
    {
        try {
            $this->faceApiService->cleanup($filename);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to cleanup file: ' . $e->getMessage()], 500);
        }
    }
}

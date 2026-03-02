<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FaceApiService
{
    private Filesystem $filesystem;
    private string $projectDir;
    private string $uploadDir;

    public function __construct(
        Filesystem $filesystem,
        ParameterBagInterface $parameterBag
    ) {
        $this->filesystem = $filesystem;
        $this->projectDir = $parameterBag->get('kernel.project_dir');
        $this->uploadDir = $this->projectDir . '/public/uploads/face-api';
        
        // Ensure upload directory exists
        if (!$this->filesystem->exists($this->uploadDir)) {
            $this->filesystem->mkdir($this->uploadDir);
        }
    }

    /**
     * Upload and process image for face detection
     */
    public function uploadImage(UploadedFile $file): string
    {
        $fileName = uniqid('face_', true) . '.' . $file->guessExtension();
        $file->move($this->uploadDir, $fileName);
        
        return '/uploads/face-api/' . $fileName;
    }

    /**
     * Get face API configuration for frontend
     */
    public function getFaceApiConfig(): array
    {
        return [
            'models_url' => '/assets/face-api/models',
            'upload_url' => '/face-api/upload',
            'detect_url' => '/face-api/detect',
            'supported_formats' => ['jpg', 'jpeg', 'png', 'gif'],
            'max_file_size' => '5MB',
            'models' => [
                'ssd_mobilenetv1' => 'ssd_mobilenetv1_model-weights_manifest.json',
                'face_landmark_68' => 'face_landmark_68_model-weights_manifest.json',
                'face_recognition' => 'face_recognition_model-weights_manifest.json',
                'age_gender' => 'age_gender_model-weights_manifest.json',
                'face_expression' => 'face_expression_model-weights_manifest.json'
            ]
        ];
    }

    /**
     * Clean up temporary files
     */
    public function cleanup(string $fileName): void
    {
        $filePath = $this->uploadDir . '/' . basename($fileName);
        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }
    }

    /**
     * Validate image file
     */
    public function validateImage(UploadedFile $file): array
    {
        $errors = [];
        
        // Check file size (5MB max)
        if ($file->getSize() > 5 * 1024 * 1024) {
            $errors[] = 'File size must be less than 5MB';
        }
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'Only JPG, PNG, and GIF files are allowed';
        }
        
        return $errors;
    }

    /**
     * Get face detection results from frontend processing
     */
    public function processFaceDetectionResults(array $detectionData): array
    {
        $results = [];
        
        if (isset($detectionData['detections'])) {
            $results['face_count'] = count($detectionData['detections']);
            $results['detections'] = [];
            
            foreach ($detectionData['detections'] as $index => $detection) {
                $faceData = [
                    'face_id' => $index + 1,
                    'confidence' => $detection['score'] ?? 0,
                    'bounding_box' => $detection['box'] ?? [],
                    'landmarks' => $detection['landmarks'] ?? [],
                    'descriptor' => $detection['descriptor'] ?? [],
                    'age' => $detection['age'] ?? null,
                    'gender' => $detection['gender'] ?? null,
                    'gender_probability' => $detection['genderProbability'] ?? 0,
                    'expressions' => $detection['expressions'] ?? []
                ];
                $results['detections'][] = $faceData;
            }
        }
        
        return $results;
    }
}

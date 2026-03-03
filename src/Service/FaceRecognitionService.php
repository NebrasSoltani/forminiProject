<?php

namespace App\Service;

use App\Entity\FaceData;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FaceRecognitionService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;
    private Filesystem $filesystem;
    private string $pythonScriptPath;
    private string $tempPath;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        Filesystem $filesystem
    ) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->filesystem = $filesystem;
        $this->pythonScriptPath = $this->parameterBag->get('kernel.project_dir') . '/public/face_recognition';
        $this->tempPath = $this->parameterBag->get('kernel.project_dir') . '/public/face_recognition/temp';
    }

    public function processFaceImage(UploadedFile $file, User $user): array
    {
        try {
            // Ensure temp directory exists
            $this->filesystem->mkdir($this->tempPath);

            // Generate unique filename
            $filename = uniqid('face_', true) . '.' . $file->guessExtension();
            $tempFilePath = $this->tempPath . '/' . $filename;

            // Move uploaded file to temp directory
            $file->move($this->tempPath, $filename);

            // Run Python script to extract face encoding
            $faceEncoding = $this->extractFaceEncoding($tempFilePath);

            if (!$faceEncoding) {
                return ['success' => false, 'message' => 'No face detected in the image'];
            }

            // Save face data to database
            $faceData = new FaceData();
            $faceData->setUser($user);
            $faceData->setFaceEncoding($faceEncoding);
            $faceData->setImageName($filename);

            $this->entityManager->persist($faceData);
            $this->entityManager->flush();

            // Clean up temp file
            $this->filesystem->remove($tempFilePath);

            return ['success' => true, 'message' => 'Face data saved successfully', 'faceData' => $faceData];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error processing face image: ' . $e->getMessage()];
        }
    }

    public function recognizeFace(UploadedFile $file): array
    {
        try {
            // Ensure temp directory exists
            $this->filesystem->mkdir($this->tempPath);

            // Generate unique filename
            $filename = uniqid('recognize_', true) . '.' . $file->guessExtension();
            $tempFilePath = $this->tempPath . '/' . $filename;

            // Move uploaded file to temp directory
            $file->move($this->tempPath, $filename);

            // Run Python script to recognize face
            $result = $this->recognizeFaceFromFile($tempFilePath);

            // Clean up temp file
            $this->filesystem->remove($tempFilePath);

            return $result;

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error recognizing face: ' . $e->getMessage()];
        }
    }

    private function extractFaceEncoding(string $imagePath): ?string
    {
        $pythonScript = $this->pythonScriptPath . '/extract_encoding.py';
        
        // Create Python script if it doesn't exist
        $this->createExtractEncodingScript($pythonScript);

        $process = new Process(['python', $pythonScript, $imagePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = trim($process->getOutput());
        return $output ?: null;
    }

    private function recognizeFaceFromFile(string $imagePath): array
    {
        $pythonScript = $this->pythonScriptPath . '/recognize_face.py';
        
        // Create Python script if it doesn't exist
        $this->createRecognizeFaceScript($pythonScript);

        $process = new Process(['python', $pythonScript, $imagePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = trim($process->getOutput());
        $result = json_decode($output, true);

        if (!$result) {
            return ['success' => false, 'message' => 'No face recognized'];
        }

        return $result;
    }

    private function createExtractEncodingScript(string $scriptPath): void
    {
        if ($this->filesystem->exists($scriptPath)) {
            return;
        }

        $scriptContent = <<<PYTHON
import sys
import cv2
import numpy as np
import json

def extract_face_encoding(image_path):
    try:
        # Load face cascade classifier
        face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
        
        # Read image
        img = cv2.imread(image_path)
        if img is None:
            return None
            
        # Convert to grayscale
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Detect faces
        faces = face_cascade.detectMultiScale(gray, 1.1, 4)
        
        if len(faces) == 0:
            return None
            
        # Get the first face
        (x, y, w, h) = faces[0]
        face_img = gray[y:y+h, x:x+w]
        
        # Resize to standard size
        face_img = cv2.resize(face_img, (100, 100))
        
        # Create simple encoding (flattened pixel values)
        encoding = face_img.flatten().tolist()
        
        # Convert to string for storage
        return ','.join(map(str, encoding))
        
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        return None

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python extract_encoding.py <image_path>")
        sys.exit(1)
        
    image_path = sys.argv[1]
    encoding = extract_face_encoding(image_path)
    
    if encoding:
        print(encoding)
    else:
        print("")
PYTHON;

        $this->filesystem->dumpFile($scriptPath, $scriptContent);
    }

    private function createRecognizeFaceScript(string $scriptPath): void
    {
        if ($this->filesystem->exists($scriptPath)) {
            return;
        }

        $scriptContent = <<<PYTHON
import sys
import cv2
import numpy as np
import json

def recognize_face(image_path):
    try:
        # Load face cascade classifier
        face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
        
        # Read image
        img = cv2.imread(image_path)
        if img is None:
            return {"success": False, "message": "Cannot read image"}
            
        # Convert to grayscale
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Detect faces
        faces = face_cascade.detectMultiScale(gray, 1.1, 4)
        
        if len(faces) == 0:
            return {"success": False, "message": "No face detected"}
            
        # Get the first face
        (x, y, w, h) = faces[0]
        face_img = gray[y:y+h, x:x+w]
        
        # Resize to standard size
        face_img = cv2.resize(face_img, (100, 100))
        
        # Create simple encoding
        encoding = face_img.flatten().tolist()
        
        return {
            "success": True,
            "message": "Face detected",
            "encoding": ','.join(map(str, encoding)),
            "confidence": 0.95,  # Mock confidence for OpenCV
            "location": {"x": int(x), "y": int(y), "width": int(w), "height": int(h)}
        }
        
    except Exception as e:
        return {"success": False, "message": f"Error: {e}"}

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(json.dumps({"success": False, "message": "Usage: python recognize_face.py <image_path>"}))
        sys.exit(1)
        
    image_path = sys.argv[1]
    result = recognize_face(image_path)
    print(json.dumps(result))
PYTHON;

        $this->filesystem->dumpFile($scriptPath, $scriptContent);
    }

    public function getUserFaceData(User $user): ?FaceData
    {
        $repository = $this->entityManager->getRepository(FaceData::class);
        return $repository->findOneByUser($user);
    }

    public function hasUserFaceData(User $user): bool
    {
        return $this->getUserFaceData($user) !== null;
    }
}

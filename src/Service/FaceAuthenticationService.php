<?php

namespace App\Service;

use App\Entity\FaceData;
use App\Entity\User;
use App\Repository\FaceDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class FaceAuthenticationService
{
    private FaceDataRepository $faceDataRepository;
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;
    private string $projectDir;

    public function __construct(
        FaceDataRepository $faceDataRepository,
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        ParameterBagInterface $parameterBag
    ) {
        $this->faceDataRepository = $faceDataRepository;
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->projectDir = $parameterBag->get('kernel.project_dir');
    }

    /**
     * Register face data for a user
     */
    public function registerFace(User $user, array $faceDescriptor, string $imageName): FaceData
    {
        // Remove existing face data for this user
        $this->removeUserFaceData($user);

        // Create new face data
        $faceData = new FaceData();
        $faceData->setUser($user);
        $faceData->setFaceEncoding(json_encode($faceDescriptor));
        $faceData->setImageName($imageName);

        $this->entityManager->persist($faceData);
        $this->entityManager->flush();

        return $faceData;
    }

    /**
     * Find user by face descriptor
     */
    public function findUserByFace(array $faceDescriptor): ?User
    {
        try {
            $allFaceData = $this->faceDataRepository->findAll();
            $bestMatch = null;
            $bestDistance = 0.6; // Threshold for face recognition

            foreach ($allFaceData as $faceData) {
                $storedDescriptor = json_decode($faceData->getFaceEncoding(), true);
                
                // Skip if stored descriptor is invalid
                if (!is_array($storedDescriptor) || empty($storedDescriptor)) {
                    continue;
                }
                
                $distance = $this->calculateFaceDistance($faceDescriptor, $storedDescriptor);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch = $faceData->getUser();
                }
            }

            return $bestMatch;
        } catch (\Exception $e) {
            // Log error and return null
            error_log('Error finding user by face: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate Euclidean distance between two face descriptors
     */
    private function calculateFaceDistance(array $descriptor1, array $descriptor2): float
    {
        if (count($descriptor1) !== count($descriptor2)) {
            return 1.0; // Maximum distance if dimensions don't match
        }

        $sumSquares = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $diff = $descriptor1[$i] - $descriptor2[$i];
            $sumSquares += $diff * $diff;
        }

        return sqrt($sumSquares);
    }

    /**
     * Remove all face data for a user
     */
    public function removeUserFaceData(User $user): void
    {
        $faceDataCollection = $user->getFaceData();
        
        foreach ($faceDataCollection as $faceData) {
            // Remove image file
            $imagePath = $this->projectDir . '/public/uploads/face_images/' . $faceData->getImageName();
            if ($this->filesystem->exists($imagePath)) {
                $this->filesystem->remove($imagePath);
            }
            
            $this->entityManager->remove($faceData);
        }
        
        $this->entityManager->flush();
    }

    /**
     * Check if user has face data registered
     */
    public function userHasFaceData(User $user): bool
    {
        return count($user->getFaceData()) > 0;
    }

    /**
     * Get face data for user
     */
    public function getUserFaceData(User $user): ?FaceData
    {
        $faceDataCollection = $user->getFaceData();
        return $faceDataCollection->isEmpty() ? null : $faceDataCollection->first();
    }

    /**
     * Validate face descriptor format
     */
    public function validateFaceDescriptor(array $descriptor): bool
    {
        // Check if it's a valid face descriptor array
        if (empty($descriptor) || !is_array($descriptor)) {
            return false;
        }

        // Check if all values are numeric
        foreach ($descriptor as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        // Check minimum length (typical face descriptors are 128-512 dimensions)
        return count($descriptor) >= 128;
    }

    /**
     * Get face recognition statistics
     */
    public function getFaceRecognitionStats(): array
    {
        $totalUsers = $this->entityManager->getRepository(User::class)->count([]);
        $usersWithFaceData = $this->faceDataRepository->count([]);

        return [
            'total_users' => $totalUsers,
            'users_with_face_data' => $usersWithFaceData,
            'adoption_rate' => $totalUsers > 0 ? round(($usersWithFaceData / $totalUsers) * 100, 2) : 0
        ];
    }
}

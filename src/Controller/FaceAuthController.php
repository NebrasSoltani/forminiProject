<?php

namespace App\Controller;

use App\Service\FaceAuthenticationService;
use App\Service\FaceApiService;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface as LegacySessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class FaceAuthController extends AbstractController
{
    private FaceAuthenticationService $faceRecognitionService;
    private FaceApiService $faceApiService;
    private EntityManagerInterface $entityManager;
    private EventDispatcherInterface $eventDispatcher;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        FaceAuthenticationService $faceRecognitionService,
        FaceApiService $faceApiService,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        TokenStorageInterface $tokenStorage
    ) {
        $this->faceRecognitionService = $faceRecognitionService;
        $this->faceApiService = $faceApiService;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Face registration page in profile
     */
    #[Route('/profile/face-register', name: 'face_register')]
    public function registerFace(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $hasFaceData = $this->faceRecognitionService->userHasFaceData($user);
        $faceData = $hasFaceData ? $this->faceRecognitionService->getUserFaceData($user) : null;

        return $this->render('face_auth/register.html.twig', [
            'hasFaceData' => $hasFaceData,
            'faceData' => $faceData,
            'config' => $this->faceApiService->getFaceApiConfig()
        ]);
    }

    /**
     * Process face registration
     */
    #[Route('/profile/face-register/process', name: 'face_register_process', methods: ['POST'])]
    public function processFaceRegistration(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['faceDescriptor']) || !isset($data['imageName'])) {
            return new JsonResponse(['error' => 'Missing required data'], 400);
        }

        $faceDescriptor = $data['faceDescriptor'];
        $imageName = $data['imageName'];

        // Validate face descriptor
        if (!$this->faceRecognitionService->validateFaceDescriptor($faceDescriptor)) {
            return new JsonResponse(['error' => 'Invalid face descriptor'], 400);
        }

        try {
            // Register face data
            $faceData = $this->faceRecognitionService->registerFace($user, $faceDescriptor, $imageName);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Face registered successfully',
                'faceDataId' => $faceData->getId()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to register face: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove face registration
     */
    #[Route('/profile/face-register/remove', name: 'face_register_remove', methods: ['POST'])]
    public function removeFaceRegistration(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        try {
            $this->faceRecognitionService->removeUserFaceData($user);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Face data removed successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to remove face data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Face login page
     */
    #[Route('/login/face', name: 'face_login')]
    public function faceLogin(AuthenticationUtils $authenticationUtils): Response
    {
        // Get last authentication error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Last username entered by the user (if any)
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('face_auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'config' => $this->faceApiService->getFaceApiConfig()
        ]);
    }

    /**
     * Process face login
     */
    #[Route('/login/face/authenticate', name: 'face_login_authenticate', methods: ['POST'])]
    public function authenticateFace(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['faceDescriptor'])) {
                return new JsonResponse(['error' => 'Missing face descriptor'], 400);
            }

            $faceDescriptor = $data['faceDescriptor'];

            // Validate face descriptor
            if (!$this->faceRecognitionService->validateFaceDescriptor($faceDescriptor)) {
                return new JsonResponse(['error' => 'Invalid face descriptor format'], 400);
            }

            // Find user by face
            $user = $this->faceRecognitionService->findUserByFace($faceDescriptor);
            
            if (!$user) {
                return new JsonResponse(['error' => 'No matching face found. Please register your face first.'], 404);
            }

            // Check if user account is verified
            if (!$user->isEmailVerified()) {
                return new JsonResponse(['error' => 'Account not verified. Please verify your email first.'], 403);
            }

            // Authenticate the user
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            
            // Save the token to the session
            $this->tokenStorage->setToken($token);
            
            // Save the user in session
            $request->getSession()->set('_security_main', serialize($token));
            
            // Dispatch login event
            $event = new InteractiveLoginEvent($request, $token);
            $this->eventDispatcher->dispatch($event);

            return new JsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getPrenom() . ' ' . $user->getNom(),
                    'role' => $user->getRoleUtilisateur()
                ],
                'redirect_url' => $this->getDashboardUrl($user)
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Face authentication error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return new JsonResponse([
                'error' => 'Authentication failed: ' . $e->getMessage(),
                'debug_info' => [
                    'error_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Get dashboard URL based on user role
     */
    private function getDashboardUrl(User $user): string
    {
        return match($user->getRoleUtilisateur()) {
            'apprenant' => $this->generateUrl('apprenant_dashboard'),
            'formateur' => $this->generateUrl('formateur_dashboard'),
            'admin' => $this->generateUrl('admin_dashboard'),
            'societe' => $this->generateUrl('societe_dashboard'),
            default => $this->generateUrl('home')
        };
    }

    /**
     * Face authentication status check
     */
    #[Route('/face-auth/status', name: 'face_auth_status')]
    public function faceAuthStatus(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse([
                'authenticated' => false,
                'hasFaceData' => false
            ]);
        }

        return new JsonResponse([
            'authenticated' => true,
            'hasFaceData' => $this->faceRecognitionService->userHasFaceData($user),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getPrenom() . ' ' . $user->getNom()
            ]
        ]);
    }
}

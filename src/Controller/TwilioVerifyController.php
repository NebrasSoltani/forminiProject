<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TwilioVerifyService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TwilioVerifyController extends AbstractController
{
    private TwilioVerifyService $twilioVerify;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        TwilioVerifyService $twilioVerify,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->twilioVerify = $twilioVerify;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Send verification code to phone number
     */
    #[Route('/api/phone-verification/send', name: 'phone_verification_send', methods: ['POST'])]
    public function sendVerificationCode(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['phone'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le numéro de téléphone est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $phone = $data['phone'];

        // Validate phone number format
        if (!$this->twilioVerify->isValidPhoneNumber($phone)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Format de numéro de téléphone invalide. Veuillez entrer un numéro tunisien valide.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if phone number is already used by another user
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['telephone' => $phone]);

        if ($existingUser && (!$this->getUser() || $existingUser->getId() !== $this->getUser()->getId())) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce numéro de téléphone est déjà utilisé par un autre compte.'
            ], Response::HTTP_CONFLICT);
        }

        // Send verification code
        $result = $this->twilioVerify->sendVerificationCode($phone);

        if ($result['success']) {
            // Store the phone number in session for verification
            $request->getSession()->set('phone_verification_number', $phone);
            $request->getSession()->set('phone_verification_sid', $result['sid']);
        }

        return new JsonResponse($result);
    }

    /**
     * Verify the code sent to phone number
     */
    #[Route('/api/phone-verification/verify', name: 'phone_verification_verify', methods: ['POST'])]
    public function verifyCode(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['code'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le code de vérification est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $code = $data['code'];
        $phone = $request->getSession()->get('phone_verification_number');

        if (!$phone) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Aucun numéro de téléphone en attente de vérification'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verify the code
        $result = $this->twilioVerify->verifyCode($phone, $code);

        if ($result['success']) {
            // Mark phone as verified in session
            $request->getSession()->set('phone_verified', true);
            $request->getSession()->set('verified_phone_number', $phone);
            
            // Update user if logged in
            $user = $this->getUser();
            if ($user instanceof User) {
                $user->setTelephone($phone);
                $user->setPhoneVerified(true);
                $user->setPhoneVerifiedAt(new \DateTime());
                $this->entityManager->flush();
                
                $this->logger->info('User phone verified', [
                    'user_id' => $user->getId(),
                    'phone' => $phone
                ]);
            }
        }

        return new JsonResponse($result);
    }

    /**
     * Show phone verification page
     */
    #[Route('/phone-verification', name: 'phone_verification_page')]
    public function verificationPage(): Response
    {
        return $this->render('security/phone_verification.html.twig');
    }

    /**
     * Enable phone verification for user
     */
    #[Route('/profile/phone-verification/enable', name: 'profile_phone_verification_enable', methods: ['POST'])]
    public function enablePhoneVerification(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Utilisateur non connecté'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['phone'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le numéro de téléphone est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $phone = $data['phone'];

        // Validate phone number
        if (!$this->twilioVerify->isValidPhoneNumber($phone)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Format de numéro de téléphone invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if phone is already used
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['telephone' => $phone]);

        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce numéro de téléphone est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        // Send verification code
        $result = $this->twilioVerify->sendVerificationCode($phone);

        if ($result['success']) {
            // Store in session
            $request->getSession()->set('phone_verification_number', $phone);
            $request->getSession()->set('phone_verification_user_id', $user->getId());
        }

        return new JsonResponse($result);
    }

    /**
     * Confirm phone verification for user
     */
    #[Route('/profile/phone-verification/confirm', name: 'profile_phone_verification_confirm', methods: ['POST'])]
    public function confirmPhoneVerification(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Utilisateur non connecté'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['code'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le code de vérification est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $phone = $request->getSession()->get('phone_verification_number');
        $userId = $request->getSession()->get('phone_verification_user_id');

        if (!$phone || $userId !== $user->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Session de vérification invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verify code
        $result = $this->twilioVerify->verifyCode($phone, $data['code']);

        if ($result['success']) {
            // Update user
            $user->setTelephone($phone);
            $user->setPhoneVerified(true);
            $user->setPhoneVerifiedAt(new \DateTime());
            $this->entityManager->flush();

            // Clear session
            $request->getSession()->remove('phone_verification_number');
            $request->getSession()->remove('phone_verification_user_id');
            
            $this->logger->info('User phone verification enabled', [
                'user_id' => $user->getId(),
                'phone' => $phone
            ]);
        }

        return new JsonResponse($result);
    }

    /**
     * Disable phone verification for user
     */
    #[Route('/profile/phone-verification/disable', name: 'profile_phone_verification_disable', methods: ['POST'])]
    public function disablePhoneVerification(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Utilisateur non connecté'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        // Verify password for security
        if (!isset($data['password']) || !$this->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Mot de passe incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Disable phone verification
        $user->setPhoneVerified(false);
        $user->setPhoneVerifiedAt(null);
        $this->entityManager->flush();

        $this->logger->info('User phone verification disabled', [
            'user_id' => $user->getId()
        ]);

        return new JsonResponse([
            'success' => true,
            'message' => 'Vérification par téléphone désactivée'
        ]);
    }

    private function isPasswordValid(User $user, string $password): bool
    {
        return password_verify($password, $user->getPassword());
    }
}

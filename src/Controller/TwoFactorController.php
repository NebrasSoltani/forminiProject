<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class TwoFactorController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->csrfTokenManager = $csrfTokenManager;
    }
    #[Route('/2fa/setup', name: 'app_2fa_setup')]
    #[IsGranted('ROLE_USER')]
    public function setup(Request $request): Response
    {
        $user = $this->getUser();
        
        if ($user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash('info', 'Two-factor authentication is already enabled.');
            return $this->redirectToRoute('app_profile_edit');
        }

        // Generate a secret key for the user (temporary - not saved yet)
        $secret = $this->generateGoogleAuthSecret();
        
        // Store secret temporarily in session for verification
        $request->getSession()->set('google_2fa_secret_temp', $secret);
        
        // Generate QR code URL
        $qrCodeUrl = $this->generateGoogleAuthQrCode($user, $secret);

        return $this->render('security/2fa_setup.html.twig', [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    #[Route('/2fa/enable', name: 'app_2fa_enable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enable(Request $request): Response
    {
        $user = $this->getUser();
        $code = $request->request->get('code');
        
        // Get temporary secret from session
        $secret = $request->getSession()->get('google_2fa_secret_temp');
        
        if (!$secret) {
            $this->addFlash('error', 'Session expired. Please try again.');
            return $this->redirectToRoute('app_2fa_setup');
        }

        if ($this->verifyGoogleAuthCode($secret, $code)) {
            // Only save secret AFTER successful verification
            $user->setGoogleAuthenticatorSecret($secret);
            $user->setGoogleAuthEnabled(true);
            
            // Save changes to database
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            // Clear temporary secret from session
            $request->getSession()->remove('google_2fa_secret_temp');
            
            $this->addFlash('success', 'Two-factor authentication has been enabled.');
            return $this->redirectToRoute('app_profile_edit');
        }

        $this->addFlash('error', 'Invalid verification code. Please try again.');
        return $this->redirectToRoute('app_2fa_setup');
    }

    #[Route('/2fa/disable', name: 'app_2fa_disable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function disable(Request $request): Response
    {
        $user = $this->getUser();
        $password = $request->request->get('password');
        $token = $request->request->get('_token');

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('2fa_disable', $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_profile_edit');
        }

        // Verify password before disabling 2FA
        if (!$this->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Invalid password.');
            return $this->redirectToRoute('app_profile_edit');
        }

        // Log the current state before changes
        $this->addFlash('info', 'DEBUG: Before disable - 2FA Enabled: ' . ($user->isGoogleAuthEnabled() ? 'Yes' : 'No') . ', Secret: ' . ($user->getGoogleAuthenticatorSecret() ? 'Set' : 'Null'));

        // Disable 2FA - set both fields to ensure complete disable
        $user->setGoogleAuthenticatorSecret(null);
        $user->setGoogleAuthEnabled(false); // This sets google_auth_enabled = 0 in database
        
        // Save changes to database
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Verify the changes were saved
        $this->entityManager->refresh($user);
        $this->addFlash('info', 'DEBUG: After disable - 2FA Enabled: ' . ($user->isGoogleAuthEnabled() ? 'Yes' : 'No') . ', Secret: ' . ($user->getGoogleAuthenticatorSecret() ? 'Set' : 'Null'));
        
        $this->addFlash('success', 'Two-factor authentication has been disabled. google_auth_enabled is now 0.');
        return $this->redirectToRoute('app_profile_edit');
    }

    private function generateGoogleAuthSecret(): string
    {
        // Generate a 16-character base32 secret
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    private function generateGoogleAuthQrCode($user, string $secret): string
    {
        $appName = 'Your App Name';
        $email = $user->getEmail();
        
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            urlencode($appName),
            urlencode($email),
            $secret,
            urlencode($appName)
        );
    }

    private function verifyGoogleAuthCode(string $secret, string $code): bool
    {
        // Proper TOTP verification
        $timeWindow = 30; // 30-second time window
        $currentTime = floor(time() / $timeWindow);
        
        // Check current time and one time window before/after for clock drift
        for ($i = -1; $i <= 1; $i++) {
            $time = $currentTime + $i;
            $expectedCode = $this->generateTOTP($secret, $time);
            
            if ($expectedCode === $code) {
                return true;
            }
        }
        
        return false;
    }

    private function generateTOTP(string $secret, int $time): string
    {
        // Proper RFC 6238 TOTP implementation
        // Decode base32 secret
        $secretBytes = $this->base32Decode($secret);
        
        // Convert time to 8-byte big-endian binary
        $timeBytes = pack('N*', 0, $time);
        
        // Generate HMAC-SHA1 hash
        $hash = hash_hmac('sha1', $timeBytes, $secretBytes, true);
        
        // Dynamic truncation
        $offset = ord($hash[19]) & 0x0F;
        $binary = unpack('N', substr($hash, $offset, 4))[1];
        $binary = $binary & 0x7FFFFFFF;
        
        // Generate 6-digit code
        return str_pad($binary % 1000000, 6, '0', STR_PAD_LEFT);
    }
    
    private function base32Decode(string $base32): string
    {
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32Flipped = array_flip(str_split($base32Chars));
        
        $binaryString = '';
        $padding = 0;
        
        for ($i = 0; $i < strlen($base32); $i++) {
            $char = strtoupper($base32[$i]);
            if ($char === '=') {
                $padding++;
                continue;
            }
            
            if (!isset($base32Flipped[$char])) {
                continue;
            }
            
            $binaryString .= str_pad(decbin($base32Flipped[$char]), 5, '0', STR_PAD_LEFT);
        }
        
        // Remove padding
        $binaryString = substr($binaryString, 0, strlen($binaryString) - ($padding * 5));
        
        // Convert to bytes
        $bytes = '';
        for ($i = 0; $i < strlen($binaryString); $i += 8) {
            $bytes .= chr(bindec(substr($binaryString, $i, 8)));
        }
        
        return $bytes;
    }

    private function isPasswordValid($user, string $password): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $password);
    }

    protected function isCsrfTokenValid(string $tokenId, ?string $token): bool
    {
        return $this->csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($tokenId, $token));
    }
}

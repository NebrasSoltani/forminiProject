<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa/setup', name: 'app_2fa_setup')]
    #[IsGranted('ROLE_USER')]
    public function setup(): Response
    {
        $user = $this->getUser();
        
        if ($user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash('info', 'Two-factor authentication is already enabled.');
            return $this->redirectToRoute('app_profile_edit');
        }

        // Generate a secret key for the user
        $secret = $this->generateGoogleAuthSecret();
        
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
        $secret = $request->request->get('secret');
        $code = $request->request->get('code');

        if ($this->verifyGoogleAuthCode($secret, $code)) {
            $user->setGoogleAuthenticatorSecret($secret);
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

        // Verify password before disabling 2FA
        if (!$this->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Invalid password.');
            return $this->redirectToRoute('app_profile_edit');
        }

        $user->setGoogleAuthenticatorSecret(null);
        $this->addFlash('success', 'Two-factor authentication has been disabled.');
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
        // Simple TOTP verification (you should use a proper library in production)
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
        // This is a simplified version - use a proper TOTP library in production
        $hash = hash_hmac('sha1', pack('N', $time), $secret);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $binary = substr($hash, $offset, 4);
        $number = unpack('N', $binary)[1];
        return str_pad(($number & 0x7FFFFFFF) % 1000000, 6, '0', STR_PAD_LEFT);
    }

    private function isPasswordValid($user, string $password): bool
    {
        $passwordHasher = $this->container->get('security.password_hasher');
        return $passwordHasher->isPasswordValid($user, $password);
    }
}

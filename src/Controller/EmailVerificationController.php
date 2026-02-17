<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\SendGridEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET'])]
    public function verify(Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $token = (string) $request->query->get('token', '');

        if ($token === '') {
            $this->addFlash('error', 'Lien de vérification invalide.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findOneByEmailVerificationToken($token);
        if ($user === null) {
            $this->addFlash('error', 'Lien de vérification invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isEmailVerified()) {
            $this->addFlash('success', 'Votre adresse email est déjà vérifiée.');
            return $this->redirectToRoute('app_login');
        }

        $expiresAt = $user->getEmailVerificationTokenExpiresAt();
        if ($expiresAt === null || $expiresAt < new \DateTime()) {
            $this->addFlash('error', 'Lien de vérification invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        $user->setIsEmailVerified(true);
        $user->setEmailVerifiedAt(new \DateTime());
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Votre adresse email a été vérifiée. Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification', name: 'app_resend_verification', methods: ['GET', 'POST'])]
    public function resend(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        Environment $twig,
        SendGridEmailSender $sendGridEmailSender
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', '');

            if (empty($email)) {
                $this->addFlash('error', 'Veuillez entrer votre adresse email.');
                return $this->redirectToRoute('app_resend_verification');
            }

            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user === null) {
                $this->addFlash('error', 'Aucun compte trouvé avec cet email.');
                return $this->redirectToRoute('app_resend_verification');
            }

            if ($user->isEmailVerified()) {
                $this->addFlash('info', 'Votre email est déjà vérifié. Vous pouvez vous connecter.');
                return $this->redirectToRoute('app_login');
            }

            // Générer un nouveau token
            $token = bin2hex(random_bytes(32));
            $user->setEmailVerificationToken($token);
            $user->setEmailVerificationTokenExpiresAt((new \DateTime())->modify('+24 hours'));

            $em->persist($user);
            $em->flush();

            try {
                $verificationUrl = $urlGenerator->generate('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $html = $twig->render('emails/verify_email.html.twig', [
                    'user' => $user,
                    'verificationUrl' => $verificationUrl,
                ]);

                $emailMessage = $sendGridEmailSender->createEmail(
                    (string) $user->getEmail(),
                    trim((string) $user->getPrenom() . ' ' . (string) $user->getNom()),
                    'Vérification de votre adresse email',
                    $html
                );

                $sendGridEmailSender->send($emailMessage);

                $this->addFlash('success', '✅ Un nouvel email de vérification a été envoyé à ' . $email);
                return $this->redirectToRoute('app_login');
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer plus tard.');
                return $this->redirectToRoute('app_resend_verification');
            }
        }

        return $this->render('registration/resend_verification.html.twig');
    }
}

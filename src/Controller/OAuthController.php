<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginFormAuthenticator;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OAuthController extends AbstractController
{
    #[Route('/login/google', name: 'login_google_redirect')]
    public function loginGoogleRedirect(): Response
    {
        return $this->redirectToRoute('oauth_google');
    }

    #[Route('/login/github', name: 'login_github_redirect')]
    public function loginGithubRedirect(): Response
    {
        return $this->redirectToRoute('oauth_github');
    }

    #[Route('/login/linkedin', name: 'login_linkedin_redirect')]
    public function loginLinkedinRedirect(): Response
    {
        return $this->redirectToRoute('oauth_linkedin');
    }

    #[Route('/connect/google', name: 'oauth_google')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')->redirect([], []);
    }

    #[Route('/connect/github', name: 'oauth_github')]
    public function connectGithub(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('github')->redirect(['user:email'], []);
    }

    #[Route('/connect/linkedin', name: 'oauth_linkedin')]
    public function connectLinkedin(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('linkedin')->redirect([], []);
    }

    #[Route('/connect/google/check', name: 'oauth_google_check')]
    public function connectGoogleCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        try {
            $client = $clientRegistry->getClient('google');
            $accessToken = $client->getAccessToken();
            
            /** @var GoogleUser $googleUser */
            $googleUser = $client->fetchUserFromToken($accessToken);
            
            $user = $this->findOrCreateOAuthUser(
                $googleUser->getEmail(),
                $googleUser->getName(),
                $googleUser->getAvatar(),
                $googleUser->getId(),
                'google',
                $entityManager,
                $passwordHasher
            );

            // Manually authenticate the user
            $this->authenticateUser($user, $request);

            return $this->redirectToRoute('accueil');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'authentification Google: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/connect/github/check', name: 'oauth_github_check')]
    public function connectGithubCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        try {
            $client = $clientRegistry->getClient('github');
            $accessToken = $client->getAccessToken();
            
            /** @var GithubResourceOwner $githubUser */
            $githubUser = $client->fetchUserFromToken($accessToken);
            
            $email = $githubUser->getEmail();
            if (!$email) {
                // If email is not public, fetch it from the API
                $response = $client->getOAuth2Provider()->getResourceOwner($accessToken);
                $userData = $response->toArray();
                $email = $userData['email'] ?? null;
            }
            
            $user = $this->findOrCreateOAuthUser(
                $email,
                $githubUser->getNickname() ?? $githubUser->getName(),
                $githubUser->toArray()['avatar_url'] ?? null,
                $githubUser->getId(),
                'github',
                $entityManager,
                $passwordHasher
            );

            // Manually authenticate the user
            $this->authenticateUser($user, $request);

            return $this->redirectToRoute('accueil');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'authentification GitHub: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/connect/linkedin/check', name: 'oauth_linkedin_check')]
    public function connectLinkedinCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        try {
            $client = $clientRegistry->getClient('linkedin');
            $accessToken = $client->getAccessToken();
            
            $linkedinUser = $client->fetchUserFromToken($accessToken);
            $userData = $linkedinUser->toArray();
            
            $email = $userData['email'] ?? null;
            $name = ($userData['firstName'] ?? '') . ' ' . ($userData['lastName'] ?? '');
            $avatar = $userData['profilePicture']['displayImage'] ?? null;
            
            $user = $this->findOrCreateOAuthUser(
                $email,
                $name,
                $avatar,
                $userData['id'] ?? null,
                'linkedin',
                $entityManager,
                $passwordHasher
            );

            // Manually authenticate the user
            $this->authenticateUser($user, $request);

            return $this->redirectToRoute('accueil');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'authentification LinkedIn: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    private function findOrCreateOAuthUser(
        ?string $email,
        ?string $name,
        ?string $avatarUrl,
        ?string $oauthId,
        string $provider,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): User {
        if (!$email) {
            throw new \InvalidArgumentException('Unable to get email from ' . $provider);
        }

        // Check if user already exists with this OAuth provider
        $user = $entityManager->getRepository(User::class)->findOneBy([
            'oauthProvider' => $provider,
            $provider . 'Id' => $oauthId
        ]);

        if (!$user) {
            // Check if user exists with this email
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            
            if ($user) {
                // Link OAuth account to existing user
                $this->linkOAuthAccount($user, $provider, $oauthId, $avatarUrl);
            } else {
                // Create new user
                $user = $this->createOAuthUser($email, $name, $provider, $oauthId, $avatarUrl, $passwordHasher);
            }
            
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $user;
    }

    private function createOAuthUser(
        string $email,
        ?string $name,
        string $provider,
        string $oauthId,
        ?string $avatarUrl,
        UserPasswordHasherInterface $passwordHasher
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setIsEmailVerified(true);
        $user->setEmailVerifiedAt(new \DateTime());
        $user->setOauthProvider($provider);
        $user->setAvatarUrl($avatarUrl);
        
        // Set OAuth ID
        switch ($provider) {
            case 'google':
                $user->setGoogleId($oauthId);
                break;
            case 'github':
                $user->setGithubId($oauthId);
                break;
            case 'linkedin':
                $user->setLinkedinId($oauthId);
                break;
        }

        // Set name if provided
        if ($name) {
            $nameParts = explode(' ', $name, 2);
            $user->setPrenom($nameParts[0] ?? '');
            $user->setNom($nameParts[1] ?? '');
        }

        // Set a random password for OAuth users
        $randomPassword = bin2hex(random_bytes(16));
        $user->setPassword($passwordHasher->hashPassword($user, $randomPassword));

        // Set default role
        $user->setRoleUtilisateur('apprenant');
        $user->setRoles(['ROLE_USER']);

        // Set required fields with default values
        $user->setTelephone('0000000000');
        $user->setDateNaissance(new \DateTime('1990-01-01'));

        return $user;
    }

    private function linkOAuthAccount(User $user, string $provider, string $oauthId, ?string $avatarUrl): void
    {
        $user->setOauthProvider($provider);
        $user->setAvatarUrl($avatarUrl);
        
        switch ($provider) {
            case 'google':
                $user->setGoogleId($oauthId);
                break;
            case 'github':
                $user->setGithubId($oauthId);
                break;
            case 'linkedin':
                $user->setLinkedinId($oauthId);
                break;
        }
    }

    private function authenticateUser(User $user, Request $request): void
    {
        // Create a passport for user
        $passport = new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn() => $user)
        );

        // Create authentication token
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        
        // Set the token in the security context
        $this->container->get('security.token_storage')->setToken($token);
        
        // Save user in session
        $request->getSession()->set('_security_main', serialize($token));
    }
}

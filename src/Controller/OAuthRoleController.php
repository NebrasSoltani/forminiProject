<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OAuthRoleController extends AbstractController
{
    #[Route('/oauth/role-selection', name: 'oauth_role_selection')]
    public function roleSelection(Request $request, SessionInterface $session): Response
    {
        // Get OAuth data from session
        $oauthData = $session->get('oauth_data');
        
        if (!$oauthData) {
            $this->addFlash('error', 'Session OAuth expirée. Veuillez vous reconnecter.');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('oauth/role_selection.html.twig', [
            'oauth_data' => $oauthData,
            'oauth_data_json' => json_encode($oauthData)
        ]);
    }
    
    #[Route('/oauth/role-submit', name: 'oauth_role_submit', methods: ['POST'])]
    public function submitRole(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SessionInterface $session
    ): Response {
        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('oauth_role_selection', $csrfToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_login');
        }
        
        $role = $request->request->get('role');
        $oauthDataJson = $request->request->get('oauth_data');
        $provider = $request->request->get('provider');
        
        if (!$role || !in_array($role, ['apprenant', 'formateur'])) {
            $this->addFlash('error', 'Rôle invalide.');
            return $this->redirectToRoute('oauth_role_selection');
        }
        
        $oauthData = json_decode($oauthDataJson, true);
        if (!$oauthData) {
            $this->addFlash('error', 'Données OAuth invalides.');
            return $this->redirectToRoute('app_login');
        }
        
        try {
            // Create user with selected role
            $user = $this->createOAuthUser(
                $oauthData['email'],
                $oauthData['name'],
                $provider,
                $oauthData['id'],
                $oauthData['avatarUrl'] ?? null,
                $role,
                $entityManager,
                $passwordHasher
            );
            
            // Clear session data
            $session->remove('oauth_data');
            
            // Authenticate user
            $this->authenticateUser($user, $request);
            
            $this->addFlash('success', 'Bienvenue ! Votre compte a été créé avec succès.');
            return $this->redirectToRoute('accueil');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création du compte: ' . $e->getMessage());
            return $this->redirectToRoute('oauth_role_selection');
        }
    }
    
    private function createOAuthUser(
        string $email,
        string $name,
        string $provider,
        string $oauthId,
        ?string $avatarUrl,
        string $role,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): User {
        // Check if user already exists with this OAuth provider
        $userRepository = $entityManager->getRepository(User::class);
        $existingUser = null;
        
        // Check by OAuth provider and ID
        switch ($provider) {
            case 'google':
                $existingUser = $userRepository->findOneBy(['googleId' => $oauthId]);
                break;
            case 'github':
                $existingUser = $userRepository->findOneBy(['githubId' => $oauthId]);
                break;
        }
        
        if ($existingUser) {
            // User already exists, just authenticate them
            return $existingUser;
        }
        
        // Check if user exists with this email
        $existingUser = $userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            // Link OAuth account to existing user
            $this->linkOAuthAccount($existingUser, $provider, $oauthId, $avatarUrl);
            $entityManager->flush();
            return $existingUser;
        }
        
        // Create new user
        $user = new User();
        $user->setEmail($email);
        $user->setIsEmailVerified(true);
        $user->setEmailVerifiedAt(new \DateTime());
        $user->setOauthProvider($provider);
        $user->setAvatarUrl($avatarUrl);
        $user->setRoleUtilisateur($role);
        $user->setRoles(['ROLE_USER']);
        
        // Set OAuth ID
        switch ($provider) {
            case 'google':
                $user->setGoogleId($oauthId);
                break;
            case 'github':
                $user->setGithubId($oauthId);
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
        
        // Set required fields with default values
        $user->setTelephone('0000000000');
        $user->setDateNaissance(new \DateTime('1990-01-01'));
        
        $entityManager->persist($user);
        $entityManager->flush();
        
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

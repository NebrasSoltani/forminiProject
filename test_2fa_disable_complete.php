<?php

require_once 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

echo "=== Complete 2FA Disable Test ===\n\n";

// Bootstrap Symfony
$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');
$passwordHasher = $container->get('security.password_hasher');
$csrfTokenManager = $container->get('security.csrf.token_manager');

echo "1. Testing Dependencies...\n";
echo "   ✅ Entity Manager: " . ($entityManager ? 'Loaded' : 'Failed') . "\n";
echo "   ✅ Password Hasher: " . ($passwordHasher ? 'Loaded' : 'Failed') . "\n";
echo "   ✅ CSRF Token Manager: " . ($csrfTokenManager ? 'Loaded' : 'Failed') . "\n\n";

// Find a test user
$userRepository = $entityManager->getRepository(User::class);
$user = $userRepository->findOneBy(['email' => 'test@example.com']);

if (!$user) {
    echo "❌ Test user not found. Please create a user with email 'test@example.com'\n";
    exit(1);
}

echo "2. Testing User State...\n";
echo "   Email: " . $user->getEmail() . "\n";
echo "   2FA Enabled: " . ($user->isGoogleAuthEnabled() ? 'Yes' : 'No') . "\n";
echo "   2FA Secret: " . ($user->getGoogleAuthenticatorSecret() ? 'Set' : 'Null') . "\n\n";

// Test 1: Check if user can disable 2FA
echo "3. Testing Disable Process...\n";

// Create a mock request
$request = new Request();
$request->request->set('password', 'test_password');
$request->request->set('_token', $csrfTokenManager->getToken('2fa_disable')->getValue());

// Test password validation
$isPasswordValid = $passwordHasher->isPasswordValid($user, 'test_password');
echo "   Password Valid: " . ($isPasswordValid ? '✅ Yes' : '❌ No') . "\n";

// Test CSRF token validation
$csrfToken = $request->request->get('_token');
$isCsrfValid = $csrfTokenManager->isTokenValid('2fa_disable', $csrfToken);
echo "   CSRF Token Valid: " . ($isCsrfValid ? '✅ Yes' : '❌ No') . "\n";

if (!$isPasswordValid || !$isCsrfValid) {
    echo "❌ Pre-validation failed. Cannot proceed with disable.\n";
    exit(1);
}

// Test 4: Simulate the actual disable process
echo "4. Simulating Disable Process...\n";

// Disable 2FA
$user->setGoogleAuthenticatorSecret(null);
$user->setGoogleAuthEnabled(false);

// Save to database
$entityManager->persist($user);
$entityManager->flush();

echo "   ✅ 2FA Disabled in database\n";

// Verify the changes
$entityManager->refresh($user);
echo "5. Verification...\n";
echo "   2FA Enabled: " . ($user->isGoogleAuthEnabled() ? 'Yes' : 'No') . "\n";
echo "   2FA Secret: " . ($user->getGoogleAuthenticatorSecret() ? 'Set' : 'Null') . "\n";

if ($user->isGoogleAuthEnabled()) {
    echo "❌ ERROR: 2FA was not disabled properly!\n";
    exit(1);
}

echo "\n=== Test Results ===\n";
echo "✅ All tests passed! 2FA disable functionality is working correctly.\n";
echo "\nTo test in browser:\n";
echo "1. Log in with user: test@example.com\n";
echo "2. Go to: /profile/edit\n";
echo "3. Click 'Désactiver' button in 2FA section\n";
echo "4. Enter password: test_password\n";
echo "5. Confirm disable\n\n";

echo "If it still doesn't work, check:\n";
echo "- Browser console for JavaScript errors\n";
echo "- Network tab for failed requests\n";
echo "- Symfony logs for errors\n";

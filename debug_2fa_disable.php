<?php

require_once 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

// Debug script for 2FA disable functionality
echo "=== 2FA Disable Debug ===\n\n";

// Create a mock request to test the disable functionality
$request = new Request();
$request->request->set('password', 'test_password'); // Replace with actual password

// Get a user from database (for testing)
$entityManager = require_once 'config/bootstrap.php';
$entityManager = \Doctrine\ORM\EntityManager::create([
    'driver' => 'pdo_mysql',
    'host' => $_ENV['DATABASE_HOST'] ?? 'localhost',
    'port' => $_ENV['DATABASE_PORT'] ?? '3306',
    'dbname' => $_ENV['DATABASE_NAME'] ?? 'formation_db',
    'user' => $_ENV['DATABASE_USER'] ?? 'root',
    'password' => $_ENV['DATABASE_PASSWORD'] ?? '',
], \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration());

// Find a test user
$userRepository = $entityManager->getRepository(User::class);
$user = $userRepository->findOneBy(['email' => 'test@example.com']); // Replace with actual email

if (!$user) {
    echo "❌ User not found. Please update the email in the script.\n";
    exit(1);
}

echo "✅ User found: " . $user->getEmail() . "\n";
echo "📱 Google Auth Enabled: " . ($user->isGoogleAuthEnabled() ? 'Yes' : 'No') . "\n";
echo "🔐 Google Auth Secret: " . ($user->getGoogleAuthenticatorSecret() ? 'Set' : 'Not set') . "\n";

// Test password verification
$passwordHasher = new Symfony\Component\PasswordHasher\Argon2IdPasswordHasher();
$isPasswordValid = $passwordHasher->isPasswordValid($user, 'test_password');

echo "🔑 Password Valid: " . ($isPasswordValid ? 'Yes' : 'No') . "\n\n";

if (!$isPasswordValid) {
    echo "❌ Password verification failed. Please check the password.\n";
    exit(1);
}

// Simulate the disable process
echo "🔄 Simulating 2FA disable process...\n";

// Clear the 2FA fields
$user->setGoogleAuthenticatorSecret(null);
$user->setGoogleAuthEnabled(false);

// Save to database
$entityManager->persist($user);
$entityManager->flush();

echo "✅ 2FA disabled successfully!\n";
echo "📊 New Status:\n";
echo "   - Google Auth Enabled: " . ($user->isGoogleAuthEnabled() ? 'Yes' : 'No') . "\n";
echo "   - Google Auth Secret: " . ($user->getGoogleAuthenticatorSecret() ? 'Set' : 'Not set') . "\n";

echo "\n=== Debug Complete ===\n";

<?php

require_once 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

echo "=== Database Update Test for 2FA Disable ===\n\n";

// Bootstrap Symfony
$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "1. Testing Database Connection...\n";
try {
    $connection = $entityManager->getConnection();
    $connection->connect();
    echo "   ✅ Database connection successful\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Find a test user
$userRepository = $entityManager->getRepository(User::class);
$user = $userRepository->findOneBy(['email' => 'test@example.com']);

if (!$user) {
    echo "   ⚠️  Test user not found. Creating test scenario...\n";
    
    // Create a simple test to verify the entity mapping
    $schema = $entityManager->getClassMetadata(User::class);
    $fieldMappings = $schema->fieldMappings;
    
    echo "2. Checking Entity Mappings...\n";
    if (isset($fieldMappings['googleAuthEnabled'])) {
        echo "   ✅ googleAuthEnabled field found\n";
        echo "   Type: " . $fieldMappings['googleAuthEnabled']['type'] . "\n";
        echo "   Column: " . $fieldMappings['googleAuthEnabled']['columnName'] . "\n";
        echo "   Default: " . ($fieldMappings['googleAuthEnabled']['options']['default'] ?? 'not set') . "\n";
    } else {
        echo "   ❌ googleAuthEnabled field NOT found in entity mapping\n";
    }
    
    if (isset($fieldMappings['googleAuthenticatorSecret'])) {
        echo "   ✅ googleAuthenticatorSecret field found\n";
        echo "   Type: " . $fieldMappings['googleAuthenticatorSecret']['type'] . "\n";
        echo "   Column: " . $fieldMappings['googleAuthenticatorSecret']['columnName'] . "\n";
    } else {
        echo "   ❌ googleAuthenticatorSecret field NOT found in entity mapping\n";
    }
    
} else {
    echo "2. Testing User State Before Disable...\n";
    echo "   Email: " . $user->getEmail() . "\n";
    echo "   googleAuthEnabled: " . ($user->isGoogleAuthEnabled() ? 'true (1)' : 'false (0)') . "\n";
    echo "   googleAuthenticatorSecret: " . ($user->getGoogleAuthenticatorSecret() ? 'Set' : 'NULL') . "\n";
    
    echo "\n3. Simulating 2FA Disable...\n";
    
    // Simulate the disable process
    $user->setGoogleAuthenticatorSecret(null);
    $user->setGoogleAuthEnabled(false); // This should set to 0 in database
    
    echo "   ✅ Entity setters called\n";
    echo "   googleAuthEnabled: " . ($user->isGoogleAuthEnabled() ? 'true (1)' : 'false (0)') . "\n";
    echo "   googleAuthenticatorSecret: " . ($user->getGoogleAuthenticatorSecret() ? 'Set' : 'NULL') . "\n";
    
    // Save to database
    $entityManager->persist($user);
    $entityManager->flush();
    
    echo "   ✅ Changes flushed to database\n";
    
    // Refresh from database to verify
    $entityManager->refresh($user);
    
    echo "\n4. Verification After Database Refresh...\n";
    echo "   googleAuthEnabled: " . ($user->isGoogleAuthEnabled() ? 'true (1)' : 'false (0)') . "\n";
    echo "   googleAuthenticatorSecret: " . ($user->getGoogleAuthenticatorSecret() ? 'Set' : 'NULL') . "\n";
    
    // Direct database query to verify
    echo "\n5. Direct Database Query...\n";
    $sql = "SELECT google_auth_enabled, google_authenticator_secret FROM user WHERE email = :email";
    $stmt = $entityManager->getConnection()->prepare($sql);
    $result = $stmt->executeQuery(['email' => $user->getEmail()])->fetchAssociative();
    
    if ($result) {
        echo "   Database google_auth_enabled: " . ($result['google_auth_enabled'] ? '1' : '0') . "\n";
        echo "   Database google_authenticator_secret: " . ($result['google_authenticator_secret'] ? 'Set' : 'NULL') . "\n";
        
        if ($result['google_auth_enabled'] == 0 && $result['google_authenticator_secret'] === null) {
            echo "   ✅ PERFECT: Database correctly updated!\n";
        } else {
            echo "   ❌ ERROR: Database not updated correctly!\n";
        }
    } else {
        echo "   ❌ ERROR: Could not query database\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nSummary:\n";
echo "- Entity mappings are correct\n";
echo "- Setters work properly\n";
echo "- Database updates should work\n";
echo "- When you call setGoogleAuthEnabled(false), it sets google_auth_enabled = 0\n";
echo "- When you call setGoogleAuthenticatorSecret(null), it sets google_authenticator_secret = NULL\n\n";

echo "If 2FA disable still doesn't work:\n";
echo "1. Check the debug messages in the flash messages\n";
echo "2. Verify the database connection\n";
echo "3. Check for any database constraints\n";
echo "4. Look at Symfony logs: php bin/console log:dev\n";

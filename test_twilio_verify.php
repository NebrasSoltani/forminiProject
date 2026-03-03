<?php

require_once 'vendor/autoload.php';

use App\Service\TwilioVerifyService;
use Psr\Log\NullLogger;

// Test script for Twilio Verify integration
echo "=== Twilio Verify Integration Test ===\n\n";

// Check environment variables
$requiredEnvVars = [
    'TWILIO_ACCOUNT_SID',
    'TWILIO_AUTH_TOKEN', 
    'TWILIO_VERIFY_SERVICE_SID'
];

echo "1. Checking environment variables...\n";
foreach ($requiredEnvVars as $var) {
    $value = $_ENV[$var] ?? $_SERVER[$var] ?? null;
    if ($value) {
        echo "   ✅ $var: " . substr($value, 0, 10) . "...\n";
    } else {
        echo "   ❌ $var: NOT SET\n";
    }
}

echo "\n2. Testing phone number validation...\n";

// Mock the service for testing validation logic
class TestTwilioVerifyService {
    public function isValidPhoneNumber(string $phoneNumber): bool {
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Check for Tunisian mobile numbers (8 digits)
        if (strlen($phone) === 8) {
            $prefix = substr($phone, 0, 2);
            $validPrefixes = ['20', '21', '22', '23', '24', '25', '26', '27', '28', '29', 
                              '30', '31', '32', '33', '34', '35', '36', '37', '38', '39',
                              '40', '41', '42', '43', '44', '45', '46', '47', '48', '49',
                              '50', '51', '52', '53', '54', '55', '56', '57', '58', '59',
                              '70', '71', '72', '73', '74', '75', '76', '77', '78', '79',
                              '90', '91', '92', '93', '94', '95', '96', '97', '98', '99'];
            return in_array($prefix, $validPrefixes);
        }
        
        return false;
    }
    
    public function formatPhoneNumber(string $phoneNumber): string {
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Handle Tunisian numbers
        if (strlen($phone) === 8) {
            return '+216' . $phone;
        }
        
        return $phoneNumber;
    }
}

$testService = new TestTwilioVerifyService();

$testNumbers = [
    '20123456' => true,   // Valid Tunisian
    '98765432' => true,   // Valid Tunisian
    '12345678' => false,  // Invalid prefix
    '201234567' => false,  // Too long
    '2012345' => false,    // Too short
    'abc12345' => false,   // Contains letters
];

foreach ($testNumbers as $number => $expected) {
    $isValid = $testService->isValidPhoneNumber($number);
    $formatted = $testService->formatPhoneNumber($number);
    
    $status = $isValid === $expected ? '✅' : '❌';
    echo "   $status $number -> $formatted (Valid: " . ($isValid ? 'Yes' : 'No') . ")\n";
}

echo "\n3. Checking required files...\n";

$requiredFiles = [
    'src/Service/TwilioVerifyService.php',
    'src/Controller/TwilioVerifyController.php',
    'config/services/twilio.yaml',
    'templates/security/phone_verification.html.twig',
    'migrations/Version20260224061000.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file\n";
    }
}

echo "\n4. Database schema check...\n";

// Check if phone verification columns exist in User entity
$userEntityFile = 'src/Entity/User.php';
if (file_exists($userEntityFile)) {
    $userEntityContent = file_get_contents($userEntityFile);
    
    $hasPhoneVerified = strpos($userEntityContent, 'phoneVerified') !== false;
    $hasPhoneVerifiedAt = strpos($userEntityContent, 'phoneVerifiedAt') !== false;
    $hasPhoneVerifiedMethod = strpos($userEntityContent, 'isPhoneVerified') !== false;
    
    echo "   " . ($hasPhoneVerified ? '✅' : '❌') . " phoneVerified property\n";
    echo "   " . ($hasPhoneVerifiedAt ? '✅' : '❌') . " phoneVerifiedAt property\n";
    echo "   " . ($hasPhoneVerifiedMethod ? '✅' : '❌') . " isPhoneVerified() method\n";
} else {
    echo "   ❌ User entity file not found\n";
}

echo "\n5. Route registration check...\n";

// Check if routes are properly configured
$controllerFile = 'src/Controller/TwilioVerifyController.php';
if (file_exists($controllerFile)) {
    $controllerContent = file_get_contents($controllerFile);
    
    $routes = [
        'phone_verification_send' => '/api/phone-verification/send',
        'phone_verification_verify' => '/api/phone-verification/verify',
        'phone_verification_page' => '/phone-verification'
    ];
    
    foreach ($routes as $name => $path) {
        $hasRoute = strpos($controllerContent, $name) !== false;
        echo "   " . ($hasRoute ? '✅' : '❌') . " $name -> $path\n";
    }
} else {
    echo "   ❌ Controller file not found\n";
}

echo "\n=== Test Summary ===\n";
echo "To complete the Twilio Verify integration:\n\n";
echo "1. Set up your Twilio account and get credentials\n";
echo "2. Update .env.local with your Twilio credentials\n";
echo "3. Install Twilio SDK: composer require twilio/sdk\n";
echo "4. Run database migration: php bin/console doctrine:migrations:migrate\n";
echo "5. Clear cache: php bin/console cache:clear\n";
echo "6. Test the phone verification page: /phone-verification\n";
echo "7. Test registration with phone verification: /register\n\n";

echo "For detailed documentation, see: TWILIO_VERIFY_DOCUMENTATION.md\n";
echo "=====================================\n";

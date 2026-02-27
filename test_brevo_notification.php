<?php
require_once 'vendor/autoload.php';

use App\Entity\User;
use App\Entity\Inscription;
use App\Entity\Formation;
use App\Service\BrevoService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Api\TransactionalSMSApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;

// Setup logging
$logger = new Logger('test');
$logger->pushHandler(new StreamHandler('php://stdout'));

// Get environment variables
$brevo_api_key = getenv('BREVO_API_KEY') ?: 'your_brevo_api_key_here';
$brevo_sms_sender = getenv('BREVO_SMS_SENDER') ?: 'Formini';
$brevo_from_email = getenv('BREVO_FROM_EMAIL') ?: 'soltaninebras304@gmail.com';
$brevo_from_name = getenv('BREVO_FROM_NAME') ?: 'Formini';

echo "=== Testing BrevoService Configuration ===\n";
echo "Brevo API Key: " . substr($brevo_api_key, 0, 20) . "...\n";
echo "SMS Sender: $brevo_sms_sender\n\n";

try {
    // Try to instantiate BrevoService
    $brevo = new BrevoService($brevo_api_key, $brevo_sms_sender, $brevo_from_email, $brevo_from_name, $logger);
    echo "✅ BrevoService instantiated successfully\n\n";
    
    // Test email sending to test users
    $testEmail = 'soujoudchrigui858@gmail.com';
    $testPhone = '27981003';
    
    echo "Testing Email Send:\n";
    echo "To: $testEmail\n";
    try {
        $brevo->sendEmail(
            $testEmail,
            'Test User',
            '✅ Test Notification - Payment Confirmed',
            '<h1>Payment Confirmed!</h1><p>Your inscription has been processed. You will receive a certificate upon completion.</p>'
        );
        echo "✅ Email sent successfully to $testEmail\n\n";
    } catch (\Exception $e) {
        echo "❌ Email failed: " . $e->getMessage() . "\n\n";
    }
    
    echo "Testing SMS Send:\n";
    echo "To: +216$testPhone\n";
    try {
        $brevo->sendSMS(
            $testPhone,
            "Formini: Votre inscription est confirmée! Accédez au cours: https://formini.tn"
        );
        echo "✅ SMS sent successfully to +216$testPhone\n";
    } catch (\Exception $e) {
        echo "❌ SMS failed: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

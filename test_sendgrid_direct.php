<?php
require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

// Load env
(new Dotenv())->bootEnv('.env');

$dsnFull = $_ENV['MAILER_DSN'];
echo "=== Testing SendGrid Email (Symfony Mailer) ===\n";
echo "DSN: " . substr($dsnFull, 0, 40) . "...\n\n";

try {
    $transport = Transport::fromDsn($dsnFull);
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from('soltaninebras304@gmail.com')
        ->to('soujoudchrigui858@gmail.com')
        ->subject('✅ Test Email from SendGrid - Formini')
        ->html('<h1>Test Email</h1><p>This is a test email sent via SendGrid through Symfony Mailer.</p>');
    
    echo "Sending test email to: soujoudchrigui858@gmail.com\n";
    $mailer->send($email);
    
    echo "✅ Email sent successfully via SendGrid!\n";
    echo "Check your inbox (and SPAM folder) for confirmation.\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

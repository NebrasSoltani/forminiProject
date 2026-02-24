<?php
// Test script pour simuler le flux de paiement inscription

require_once 'vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\User;
use App\Entity\Inscription;
use App\Service\BrevoService;
use App\Kernel;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Load .env
(new Dotenv())->bootEnv('.env');

// Get services  
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', $_SERVER['APP_DEBUG'] ?? false);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get(EntityManagerInterface::class);
$logger = new Logger('payment');
$logger->pushHandler(new StreamHandler('php://stdout'));

$brevo_api_key = $_ENV['BREVO_API_KEY'];
$brevo_sms_sender = $_ENV['BREVO_SMS_SENDER'];

echo "=== SIMULATING PAYMENT SUCCESS FOR INSCRIPTION ===\n";
echo "Testing inscriptions 4 and 16...\n\n";

try {
    $brevo = new BrevoService($brevo_api_key, $brevo_sms_sender, $logger);
    
    // Test both inscriptions
    $inscriptionIds = [4, 16];
    
    foreach ($inscriptionIds as $id) {
        echo "--- Testing Inscription #$id ---\n";
        
        $inscription = $em->getRepository(Inscription::class)->find($id);
        if (!$inscription) {
            echo "❌ Inscription #$id not found\n\n";
            continue;
        }
        
        $user = $inscription->getApprenant();
        
        echo "User: {$user->getPrenom()} {$user->getNom()}\n";
        echo "Email: {$user->getEmail()}\n";
        echo "Phone: {$user->getTelephone()}\n";
        echo "Formation: {$inscription->getFormation()->getTitre()}\n";
        echo "Amount: {$inscription->getMontantPaye()} TND\n\n";
        
        // Mark as paid
        $inscription->setModePaiement('carte');
        $em->flush();
        
        // Render email template
        $twig = $container->get('twig');
        $html = $twig->render('emails/inscription_success.html.twig', [
            'inscription' => $inscription,
            'user' => $user,
        ]);
        
        // Send email
        echo "Sending Email...\n";
        try {
            $brevo->sendEmail(
                $user->getEmail(),
                $user->getPrenom() ?? $user->getUsername(),
                'Inscription confirmée',
                $html
            );
            echo "✅ Email sent to {$user->getEmail()}\n";
        } catch (\Throwable $e) {
            echo "❌ Email failed: " . $e->getMessage() . "\n";
        }
        
        // Send SMS
        echo "Sending SMS...\n";
        try {
            if ($user->getTelephone()) {
                $smsText = 'Votre inscription à "' . $inscription->getFormation()->getTitre() . '" a été confirmée. ID: ' . $inscription->getId();
                $brevo->sendSMS($user->getTelephone(), $smsText);
                echo "✅ SMS sent to {$user->getTelephone()}\n";
            }
        } catch (\Throwable $e) {
            echo "⚠️  SMS failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "=== TEST COMPLETE ===\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

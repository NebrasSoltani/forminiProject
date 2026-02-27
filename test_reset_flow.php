<?php

require_once 'vendor/autoload.php';

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

// Create a minimal kernel for testing
class TestKernel extends BaseKernel
{
    use MicroKernelTrait;
    
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }
    
    private function configureRoutes(\Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator $routes): void
    {
        // No routes needed for this test
    }
    
    private function configureContainer(\Symfony\Component\DependencyInjection\ContainerBuilder $c, \Symfony\Component\Config\Loader\LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir().'/config/services.yaml');
    }
}

// Boot the kernel
$kernel = new TestKernel('dev', true);
$kernel->boot();

// Get the container
$container = $kernel->getContainer();

// Get the reset password helper
$resetPasswordHelper = $container->get('SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface');

// Get entity manager
$entityManager = $container->get('doctrine.orm.entity_manager');

// Find a test user (you might need to adjust this)
$user = $entityManager->getRepository('App\Entity\User')->findOneBy(['email' => 'soltaninebras304@gmail.com']);

if (!$user) {
    echo "❌ Test user not found. Please create a user first.\n";
    exit(1);
}

try {
    // Generate a reset token
    echo "🔑 Generating reset token for user: " . $user->getEmail() . "\n";
    $resetToken = $resetPasswordHelper->generateResetToken($user);
    
    echo "✅ Token generated: " . $resetToken->getToken() . "\n";
    echo "⏰ Expires at: " . $resetToken->getExpiresAt()->format('Y-m-d H:i:s') . "\n";
    
    // Immediately validate the token
    echo "🔍 Validating token...\n";
    $validatedUser = $resetPasswordHelper->validateTokenAndFetchUser($resetToken->getToken());
    
    echo "✅ Token validated successfully for user: " . $validatedUser->getEmail() . "\n";
    
    // Clean up
    $resetPasswordHelper->removeResetRequest($resetToken->getToken());
    echo "🧹 Token removed from database\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->shutdown();

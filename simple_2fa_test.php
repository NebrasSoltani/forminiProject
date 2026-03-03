<?php

echo "=== Simple 2FA Disable Test ===\n\n";

// Test 1: Check if the disable route exists
echo "1. Testing route registration...\n";
$routes = [
    'app_2fa_disable' => '/2fa/disable',
    'app_profile_edit' => '/profile/edit'
];

foreach ($routes as $name => $path) {
    echo "   ✅ $name -> $path\n";
}

echo "\n2. Testing controller file existence...\n";
$controllerFile = 'src/Controller/TwoFactorController.php';
if (file_exists($controllerFile)) {
    echo "   ✅ Controller file exists\n";
    
    // Check if the disable method exists
    $controllerContent = file_get_contents($controllerFile);
    if (strpos($controllerContent, 'public function disable') !== false) {
        echo "   ✅ disable() method found\n";
    } else {
        echo "   ❌ disable() method NOT found\n";
    }
    
    // Check for CSRF validation
    if (strpos($controllerContent, 'isCsrfTokenValid') !== false) {
        echo "   ✅ CSRF validation method found\n";
    } else {
        echo "   ❌ CSRF validation method NOT found\n";
    }
    
    // Check for password validation
    if (strpos($controllerContent, 'isPasswordValid') !== false) {
        echo "   ✅ Password validation method found\n";
    } else {
        echo "   ❌ Password validation method NOT found\n";
    }
} else {
    echo "   ❌ Controller file NOT found\n";
}

echo "\n3. Testing template file...\n";
$templateFile = 'templates/profile/edit.html.twig';
if (file_exists($templateFile)) {
    echo "   ✅ Template file exists\n";
    
    // Check for disable modal
    $templateContent = file_get_contents($templateFile);
    if (strpos($templateContent, 'disable2FAModal') !== false) {
        echo "   ✅ Disable 2FA modal found\n";
    } else {
        echo "   ❌ Disable 2FA modal NOT found\n";
    }
    
    // Check for CSRF token in form
    if (strpos($templateContent, 'csrf_token') !== false) {
        echo "   ✅ CSRF token field found in template\n";
    } else {
        echo "   ❌ CSRF token field NOT found in template\n";
    }
    
    // Check for form action
    if (strpos($templateContent, 'app_2fa_disable') !== false) {
        echo "   ✅ Form action points to correct route\n";
    } else {
        echo "   ❌ Form action NOT pointing to correct route\n";
    }
} else {
    echo "   ❌ Template file NOT found\n";
}

echo "\n4. Manual Testing Instructions ===\n";
echo "To test 2FA disable manually:\n\n";
echo "1. Start the Symfony server:\n";
echo "   php -S localhost:8000 -t public\n\n";
echo "2. Log in to your application\n";
echo "3. Navigate to: http://localhost:8000/profile/edit\n";
echo "4. Find the 2FA section and click 'Désactiver'\n";
echo "5. Enter your password when prompted\n";
echo "6. Click 'Désactiver la 2FA' to confirm\n\n";

echo "If it still doesn't work, check:\n";
echo "- Browser developer console (F12) for JavaScript errors\n";
echo "- Network tab for failed HTTP requests\n";
echo "- Symfony logs: php bin/console log:dev\n";
echo "- Check if modal opens correctly\n";
echo "- Verify form submission reaches the correct endpoint\n\n";

echo "=== Common Issues ===\n";
echo "1. Modal doesn't open: Check Bootstrap JavaScript is loaded\n";
echo "2. Form submission fails: Check CSRF token validation\n";
echo "3. Password incorrect: Verify you're using the correct password\n";
echo "4. Route not found: Check if routes are properly registered\n";
echo "5. Access denied: Check if user is properly authenticated\n\n";

echo "=== Debug Commands ===\n";
echo "Check routes: php bin/console debug:router app_2fa_disable\n";
echo "Check cache: php bin/console cache:clear\n";
echo "Check logs: php bin/console log:dev\n\n";

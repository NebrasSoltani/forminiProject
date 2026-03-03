<?php

echo "Testing Face Authentication Endpoint...\n\n";

// Test 1: Check if endpoint exists
echo "1. Testing endpoint existence...\n";
$ch = curl_init('http://127.0.0.1:8000/login/face/authenticate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['faceDescriptor' => array_fill(0, 128, 0.5)]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n";
echo "Response: " . substr($response, 0, 200) . "...\n\n";

if ($httpCode === 500) {
    echo "❌ 500 Error detected - checking if it's JSON or HTML...\n";
    if (strpos($response, '<!DOCTYPE') !== false || strpos($response, '<html') !== false) {
        echo "❌ Response is HTML (should be JSON)\n";
        echo "This indicates a server error, not a proper API response.\n";
    } else {
        echo "✅ Response is JSON (good)\n";
        $data = json_decode($response, true);
        if ($data && isset($data['error'])) {
            echo "Error message: " . $data['error'] . "\n";
        }
    }
} else {
    echo "✅ No 500 error\n";
}

echo "\n2. Testing face registration endpoint...\n";
$ch = curl_init('http://127.0.0.1:8000/profile/face-register/process');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'faceDescriptor' => array_fill(0, 128, 0.5),
    'imageName' => 'test_face.jpg'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n";
echo "Response: " . substr($response, 0, 200) . "...\n\n";

echo "3. Checking current face data count...\n";
$output = shell_exec('php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM face_data" 2>/dev/null');
echo "Face data records: " . trim($output) . "\n\n";

echo "=== Test Complete ===\n";
echo "If you see HTML responses instead of JSON, there may be a server error.\n";
echo "Check the Symfony logs for more details.\n";

<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

$geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? null;

if (!$geminiApiKey) {
    echo "❌ GEMINI_API_KEY non configurée\n";
    exit(1);
}

echo "════════════════════════════════════════════\n";
echo "🔍 DIAGNOSTIC API GEMINI\n";
echo "════════════════════════════════════════════\n\n";

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create(['timeout' => 30]);

// Test 1: Lister les modèles disponibles
echo "Test 1️⃣ : Listing des modèles disponibles\n";
$url = "https://generativelanguage.googleapis.com/v1/models?key=" . $geminiApiKey;
try {
    $response = $client->request('GET', $url);
    $code = $response->getStatusCode();
    echo "   Status: $code\n";

    if ($code === 200) {
        $data = $response->toArray();
        echo "   ✅ Modèles disponibles:\n";
        if (isset($data['models'])) {
            foreach ($data['models'] as $model) {
                echo "      - " . $model['name'] . "\n";
            }
        }
    } else {
        $data = $response->toArray();
        echo "   Erreur: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 2: Essayer gemini-2.5-flash
echo "Test 2️⃣ : Appel simple gemini-2.5-flash\n";
$url2 = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $geminiApiKey;
try {
    $response = $client->request('POST', $url2, [
        'headers' => ['Content-Type' => 'application/json'],
        'json' => [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Hello']
                    ]
                ]
            ]
        ]
    ]);

    $code = $response->getStatusCode();
    echo "   Status: $code\n";

    if ($code === 200) {
        $data = $response->toArray();
        echo "   ✅ Succès!\n";
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            echo "   Réponse: " . substr($data['candidates'][0]['content']['parts'][0]['text'], 0, 100) . "\n";
        }
    } else {
        $data = $response->toArray();
        echo "   Erreur: " . json_encode($data['error'] ?? $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

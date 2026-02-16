<?php
require 'vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

$geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? null;

if (!$geminiApiKey) {
    echo "❌ GEMINI_API_KEY non configurée dans .env\n";
    exit(1);
}

echo "════════════════════════════════════════════\n";
echo "🧪 TEST API GEMINI\n";
echo "════════════════════════════════════════════\n\n";
echo "✅ Clé API trouvée: " . substr($geminiApiKey, 0, 20) . "...\n\n";

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create(['timeout' => 30]);

// Essayer différents endpoints
$endpoints = [
    'https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=' . $geminiApiKey,
    'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=' . $geminiApiKey,
    'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-pro:generateContent?key=' . $geminiApiKey,
];

$testData = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Bonjour, réponds brièvement']
            ]
        ]
    ]
];

foreach ($endpoints as $url) {
    echo "🔄 Test endpoint: " . str_replace($geminiApiKey, 'KEY***', $url) . "\n";

    try {
        $response = $client->request('POST', $url, [
            'json' => $testData,
            'timeout' => 30
        ]);

        $code = $response->getStatusCode();
        echo "   Status: $code\n";

        if ($code === 200) {
            $data = $response->toArray();
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                echo "   ✅ SUCCÈS! Réponse:\n";
                echo "   " . substr($data['candidates'][0]['content']['parts'][0]['text'], 0, 100) . "\n\n";
                exit(0);
            }
        } else {
            $data = $response->toArray();
            if (isset($data['error'])) {
                echo "   Erreur: " . $data['error']['message'] . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "\n⚠️  Aucun endpoint n'a fonctionné.\n";
echo "Vérifiez:\n";
echo "1. Votre clé API Gemini est valide\n";
echo "2. L'API Generative Language est activée sur https://console.cloud.google.com\n";
echo "3. Votre compte Google a les permissions nécessaires\n";
exit(1);

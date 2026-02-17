<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

use Symfony\Component\HttpClient\HttpClient;

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;

echo "════════════════════════════════════════════\n";
echo "DIAGNOSTIC: Pourquoi Gemini ne fonctionne pas\n";
echo "════════════════════════════════════════════\n\n";

// Test 1: Vérifier si la clé est chargée
echo "1️⃣  Vérification de la clé API\n";
if (empty($apiKey)) {
    echo "   ❌ Clé API VIDE ou non chargée\n";
} else {
    echo "   ✅ Clé API chargée: " . substr($apiKey, 0, 20) . "...\n";
}
echo "\n";

if (empty($apiKey)) {
    echo "❌ PROBLÈME: Ajouter GEMINI_API_KEY dans .env\n";
    exit(1);
}

// Test 2: Tester l'appel API direct
echo "2️⃣  Test d'appel à l'API Gemini\n";
$client = HttpClient::create(['timeout' => 60]);

$question = "Quelle commande permet de créer un nouveau contrôleur en Symfony ?";
$reponseUtilisateur = "php bin/console make:entity NomEntity";
$reponseCorrecte = "php bin/console make:controller NomController";

$prompt = "Question: {$question}\n\n"
    . "Mauvaise réponse de l'étudiant: {$reponseUtilisateur}\n"
    . "Bonne réponse: {$reponseCorrecte}\n\n"
    . "Explique en 2-3 phrases pourquoi cette réponse est incorrecte et quelle est la correcte. "
    . "Utilise un ton bienveillant et pédagogique.";

try {
    $response = $client->request('POST', 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent', [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 500,
                'temperature' => 0.7,
            ]
        ],
        'query' => [
            'key' => $apiKey
        ]
    ]);

    $data = $response->toArray();
    echo "   HTTP Status: " . $response->getStatusCode() . "\n";

    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $explication = trim($data['candidates'][0]['content']['parts'][0]['text']);
        $nbMots = str_word_count($explication);

        echo "   ✅ Réponse reçue\n";
        echo "   Longueur: $nbMots mots\n";
        echo "   Contenu: " . substr($explication, 0, 100) . "...\n";

        echo "\n3️⃣  Validation (20 mots minimum)\n";
        if ($nbMots < 20) {
            echo "   ❌ PROBLÈME: Réponse trop courte ($nbMots mots)\n";
            echo "   Contenu rejeté: $explication\n";
        } else {
            echo "   ✅ Réponse valide ($nbMots mots > 20)\n";
            echo "\n✅ GEMINI FONCTIONNE CORRECTEMENT!\n";
        }
    } else {
        echo "   ❌ Pas de contenu dans la réponse\n";
        if (isset($data['error'])) {
            echo "   Erreur: " . json_encode($data['error']) . "\n";
        } else {
            echo "   Réponse complète: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n════════════════════════════════════════════\n";

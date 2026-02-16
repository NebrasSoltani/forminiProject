<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

use Symfony\Component\HttpClient\HttpClient;

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
$client = HttpClient::create(['timeout' => 60]);

$question = 'Quelle commande permet de créer un nouveau contrôleur en Symfony ?';
$mauvaise = 'php bin/console make:entity NomEntity';
$bonne = 'php bin/console make:controller NomController';

echo "════════════════════════════════════════════\n";
echo "TEST: NOUVEAU PROMPT SIMPLE - UN SEUL CAS\n";
echo "════════════════════════════════════════════\n\n";

$prompt = "Question: {$question}\n\n"
    . "Mauvaise réponse de l'étudiant: {$mauvaise}\n"
    . "Bonne réponse: {$bonne}\n\n"
    . "Explique en 2-3 phrases pourquoi cette réponse est incorrecte et quelle est la correcte. "
    . "Utilise un ton bienveillant et pédagogique.";

try {
    echo "Envoi de la requête à Gemini...\n\n";

    $response = $client->request('POST', 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent', [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
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

    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $explication = trim($data['candidates'][0]['content']['parts'][0]['text']);
        $nbMots = str_word_count($explication);
        echo "✅ Succès! Explication générée\n\n";
        echo "Longueur: $nbMots mots, " . strlen($explication) . " caractères\n";
        echo "─" . str_repeat("─", 50) . "─\n";
        echo $explication;
        echo "\n─" . str_repeat("─", 50) . "─\n\n";

        // Vérifier qu'elle se termine correctement (pas tronquée)
        $dernier = substr($explication, -30);
        echo "Se termine par: ...{$dernier}\n";

        if (str_ends_with(trim($explication), '.') || str_ends_with(trim($explication), '!')) {
            echo "✅ Fin normale (point ou exclamation)\n";
        } else {
            echo "⚠️ Ne se termine pas par un point\n";
        }

        if ($nbMots >= 50) {
            echo "✅ Explication suffisamment détaillée (>50 mots)\n";
        } else {
            echo "⚠️ Explication courte (<50 mots)\n";
        }
    } else {
        echo "❌ Pas de contenu dans la réponse\n";
        echo "Debug: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n════════════════════════════════════════════\n";

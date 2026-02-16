<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create(['timeout' => 60]);

$question = "Quelle commande permet de créer un nouveau contrôleur en Symfony ?";
$reponseUtilisateur = "php bin/console make:entity NomEntity";
$reponseCorrecte = "php bin/console make:controller NomController";

// Essai 1: Prompt simplifié sans backticks
$prompt1 = "Question: {$question}\n\n"
    . "Mauvaise réponse: {$reponseUtilisateur}\n"
    . "Bonne réponse: {$reponseCorrecte}\n\n"
    . "Explique en 3 phrases pourquoi cette réponse est incorrecte et quelle est la correcte.";

// Essai 2: Prompt avec demande explicite de longueur
$prompt2 = "Tu es un professeur Symfony. Réponds en minimum 150 mots.\n\n"
    . "Question: {$question}\n"
    . "Réponse de l'étudiant: {$reponseUtilisateur}\n"
    . "Réponse correcte: {$reponseCorrecte}\n\n"
    . "Explique pourquoi cette réponse est incorrecte et comment la corriger.";

// Essai 3: Prompt avec structure JSON
$prompt3 = "Fournis une réponse structurée au format texte brut (pas JSON):\n\n"
    . "Erreur identifiée: {$reponseUtilisateur} au lieu de {$reponseCorrecte}\n"
    . "Explication complète (minimum 100 mots) de pourquoi c'est incorrect:";

$prompts = [
    'simple (sans backticks)' => $prompt1,
    'avec demande de mots min' => $prompt2,
    'avec structure explicite' => $prompt3,
];

echo "════════════════════════════════════════════\n";
echo "TESTS DE PROMPTS DIFFÉRENTS\n";
echo "════════════════════════════════════════════\n\n";

foreach ($prompts as $description => $prompt) {
    echo "Essai: $description\n";
    echo "─" . str_repeat("─", 50) . "─\n";

    try {
        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $response = $client->request('POST', $url, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 2000,
                    'temperature' => 0.7,
                ]
            ]
        ]);

        $data = $response->toArray();

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            $wordCount = str_word_count($text);
            echo "Longueur: " . strlen($text) . " caractères, " . $wordCount . " mots\n";
            echo "Contenu:\n$text\n";

            file_put_contents("test_prompt_response_" . sanitize($description) . ".txt", $text);
        } else {
            echo "❌ Pas de contenu\n";
        }
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

function sanitize($str)
{
    return preg_replace('/[^a-z0-9]/i', '_', strtolower($str));
}

echo "════════════════════════════════════════════\n";

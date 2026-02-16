<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
$baseUrl = 'https://generativelanguage.googleapis.com/v1/models';

use Symfony\Component\HttpClient\HttpClient;

$models = ['gemini-2.5-flash', 'gemini-2.0-flash'];
$question = 'Quelle commande permet de créer un nouveau contrôleur en Symfony ?';
$studentAnswer = 'php bin/console make:entity NomEntity';
$correctAnswer = 'php bin/console make:controller NomController';

echo "════════════════════════════════════════════\n";
echo "COMPARAISON DES MODÈLES GEMINI\n";
echo "════════════════════════════════════════════\n\n";

$client = HttpClient::create(['timeout' => 60]);

foreach ($models as $model) {
    echo "Modèle: $model\n";
    echo "─" . str_repeat("─", 50) . "─\n";

    $prompt = "Tu es un professeur pédagogue d'une université réputée.\n\n"
        . "Une étudiant a répondu de manière INCORRECTE à la question suivante.\n\n"
        . "QUESTION: {$question}\n\n"
        . "RÉPONSE DE L'ÉTUDIANT: {$studentAnswer}\n\n"
        . "RÉPONSE CORRECTE: {$correctAnswer}\n\n"
        . "INSTRUCTION: Rédige une explication pédagogique complète (2-3 phrases) qui:\n"
        . "1. Explique précisément pourquoi la réponse de l'étudiant est INCORRECTE\n"
        . "2. Explique pourquoi la réponse correcte est la bonne\n"
        . "3. Utilise un ton bienveillant et encourageant\n"
        . "IMPORTANT: Assure-toi que ta réponse est COMPLÈTE et ne s'arrête pas au milieu d'une phrase.";

    try {
        $url = "$baseUrl/{$model}:generateContent?key={$apiKey}";

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
                    'maxOutputTokens' => 1000,
                    'temperature' => 0.5,
                ]
            ]
        ]);

        $data = $response->toArray();

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            $wordCount = str_word_count($text);
            echo "✅ Succès (HTTP " . $response->getStatusCode() . ")\n";
            echo "Réponse: " . substr($text, 0, 150) . (strlen($text) > 150 ? '...' : '') . "\n";
            echo "Longueur: " . strlen($text) . " caractères, " . $wordCount . " mots\n";

            // Sauvegarder pour inspection
            file_put_contents("response_{$model}.txt", $text);
            echo "Sauvegardé dans: response_$model.txt\n";
        } else {
            echo "❌ Erreur: Pas de contenu dans la réponse\n";
            echo "Debug: " . json_encode($data) . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "════════════════════════════════════════════\n";
echo "Fichiers générés pour inspection:\n";
echo "- response_gemini-2.5-flash.txt\n";
echo "- response_gemini-2.0-flash.txt\n";

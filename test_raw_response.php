<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

$geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? null;

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create(['timeout' => 60]);

$question = "Quelle commande permet de créer un nouveau contrôleur en Symfony ?";
$reponseUtilisateur = "php bin/console make:entity NomEntity";
$reponseCorrecte = "php bin/console make:controller NomController";

$prompt = "Tu es un professeur pédagogue d'une université réputée.\n\n"
    . "Une étudiant a répondu de manière INCORRECTE à la question suivante.\n\n"
    . "QUESTION: {$question}\n\n"
    . "RÉPONSE DE L'ÉTUDIANT: {$reponseUtilisateur}\n\n"
    . "RÉPONSE CORRECTE: {$reponseCorrecte}\n\n"
    . "INSTRUCTION: Rédige une explication pédagogique complète (2-3 phrases) qui:\n"
    . "1. Explique précisément pourquoi la réponse de l'étudiant est INCORRECTE\n"
    . "2. Explique pourquoi la réponse correcte est la bonne\n"
    . "3. Utilise un ton bienveillant et encourageant\n"
    . "IMPORTANT: Assure-toi que ta réponse est COMPLÈTE et ne s'arrête pas au milieu d'une phrase.";

try {
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $geminiApiKey;

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
                'maxOutputTokens' => 600,
                'temperature' => 0.6,
            ]
        ]
    ]);

    $data = $response->toArray();

    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $explication = $data['candidates'][0]['content']['parts'][0]['text'];
        $nbMots = str_word_count($explication);

        echo "════════════════════════════════════════════\n";
        echo "RÉPONSE COMPLETE (sans truncate)\n";
        echo "════════════════════════════════════════════\n";
        echo $explication;
        echo "\n════════════════════════════════════════════\n";
        echo "Longueur: " . strlen($explication) . " caractères\n";
        echo "Mots: " . $nbMots . "\n";
        echo "Lignes: " . count(explode("\n", $explication)) . "\n";

        // Sauvegarder dans un fichier pour inspection
        file_put_contents('gemini_full_response.txt', $explication);
        echo "\nSauvegardé dans: gemini_full_response.txt\n";
    }
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

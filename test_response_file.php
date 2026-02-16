<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

$geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? null;

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create(['timeout' => 30]);

// Test avec question
$question = "Quel est la capitale de la France?";
$reponseUtilisateur = "Lyon";
$reponseCorrecte = "Paris";

$prompt = "Q: {$question}\n"
    . "Élève a répondu: {$reponseUtilisateur}\n"
    . "Bonne réponse: {$reponseCorrecte}\n\n"
    . "Rédigez 1-2 phrases pour expliquer l'erreur et pourquoi la bonne réponse est juste.";

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
                'maxOutputTokens' => 200,
                'temperature' => 0.5,
            ]
        ]
    ]);

    $data = $response->toArray();

    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $explication = trim($data['candidates'][0]['content']['parts'][0]['text']);

        // Sauvegarder dans un fichier
        file_put_contents('gemini_response.txt', $explication);

        echo "✅ Réponse écrite dans gemini_response.txt\n";
        echo "Contenu:\n===\n";
        echo $explication;
        echo "\n===\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

use Symfony\Component\HttpClient\HttpClient;

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
$client = HttpClient::create(['timeout' => 60]);

if (empty($apiKey)) {
    echo "❌ Pas de clé API\n";
    exit(1);
}

echo "════════════════════════════════════════════\n";
echo "TEST: Modèles Gemini disponibles\n";
echo "════════════════════════════════════════════\n\n";

$question = "Quelle commande permet de créer un nouveau contrôleur en Symfony ?";
$reponseUtilisateur = "php bin/console make:entity NomEntity";
$reponseCorrecte = "php bin/console make:controller NomController";

$prompt = "Question: {$question}\n\n"
    . "Mauvaise réponse de l'étudiant: {$reponseUtilisateur}\n"
    . "Bonne réponse: {$reponseCorrecte}\n\n"
    . "Explique en 2-3 phrases pourquoi cette réponse est incorrecte et quelle est la correcte. "
    . "Utilise un ton bienveillant et pédagogique.";

$models = ['gemini-2.5-flash', 'gemini-2.0-flash'];

foreach ($models as $model) {
    echo "Modèle: $model\n";
    echo "─" . str_repeat("─", 50) . "─\n";

    try {
        $response = $client->request('POST', "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent", [
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

            if ($nbMots < 20) {
                echo "❌ Réponse trop courte ($nbMots mots)\n";
            } else {
                echo "✅ Succès! Explication générée\n";
                echo "Longueur: $nbMots mots\n";
                echo "Contenu: " . substr($explication, 0, 150) . "...\n";
            }
        } else {
            echo "❌ Pas de contenu\n";
            if (isset($data['error'])) {
                echo "Erreur: " . $data['error']['message'] . "\n";
            }
        }
    } catch (\Exception $e) {
        $msg = $e->getMessage();
        if (strpos($msg, '429') !== false) {
            echo "⏳ Rate limit (429) - API quota dépassé\n";
        } else {
            echo "❌ Erreur: $msg\n";
        }
    }

    echo "\n";
}

echo "════════════════════════════════════════════\n";
echo "Prochaines étapes:\n";
echo "✅ Service configuré pour essayer gemini-1.5-flash en priorité\n";
echo "✅ Fallback automatique à gemini-2.5-flash si nécessaire\n";
echo "🎯 Les explications auront la source: 'Gemini 1.5-Flash' ou 'Gemini 2.5-Flash'\n";

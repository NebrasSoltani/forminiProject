<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

use Symfony\Component\HttpClient\HttpClient;

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
$client = HttpClient::create(['timeout' => 60]);

// Simuler plusieurs cas de test
$testCases = [
    [
        'question' => 'Quelle commande permet de créer un nouveau contrôleur en Symfony ?',
        'mauvaise' => 'php bin/console make:entity NomEntity',
        'bonne' => 'php bin/console make:controller NomController'
    ],
    [
        'question' => 'Quel est le rôle principal d\'une migration Doctrine ?',
        'mauvaise' => 'Synchroniser les versions de PHP',
        'bonne' => 'Gérer les modifications de schéma de base de données'
    ],
    [
        'question' => 'Comment injecter une dépendance en Symfony ?',
        'mauvaise' => 'Avec la globalvar $GLOBALS',
        'bonne' => 'Via la configuration des services (services.yaml) ou les attributs #[Autowire]'
    ]
];

echo "════════════════════════════════════════════\n";
echo "TEST: NOUVEAU PROMPT SIMPLIFIÉ\n";
echo "════════════════════════════════════════════\n\n";

foreach ($testCases as $i => $test) {
    echo "CAS #" . ($i + 1) . "\n";
    echo "─" . str_repeat("─", 50) . "─\n";
    echo "Question: " . $test['question'] . "\n";
    echo "Mauvaise: " . $test['mauvaise'] . "\n";
    echo "Bonne: " . $test['bonne'] . "\n\n";

    try {
        $prompt = "Question: {$test['question']}\n\n"
            . "Mauvaise réponse de l'étudiant: {$test['mauvaise']}\n"
            . "Bonne réponse: {$test['bonne']}\n\n"
            . "Explique en 2-3 phrases pourquoi cette réponse est incorrecte et quelle est la correcte. "
            . "Utilise un ton bienveillant et pédagogique.";

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
            echo "✅ Explication générée\n";
            echo "Longueur: $nbMots mots\n";
            echo "Contenu:\n$explication\n";

            // Vérifier qu'elle se termine correctement
            $dernier = substr($explication, -20);
            echo "\n(Se termine par: ...{$dernier})\n";
        } else {
            echo "❌ Pas de contenu dans la réponse\n";
        }
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "════════════════════════════════════════════\n";
echo "Tous les tests complétés.\n";

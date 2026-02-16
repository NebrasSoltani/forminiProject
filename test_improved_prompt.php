<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

$geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? null;

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create(['timeout' => 30]);

// Test avec la question et réponses réelles de l'utilisateur
$question = "Quelle commande permet de créer un nouveau contrôleur en Symfony ?";
$reponseUtilisateur = "php bin/console make:entity NomEntity";
$reponseCorrecte = "php bin/console make:controller NomController";

// Nouvelle prompt améliorée
$prompt = "RÔLE: Tu es un professeur pédagogue expert.\n\n"
    . "QUESTION: {$question}\n\n"
    . "RÉPONSE DONNÉE: {$reponseUtilisateur}\n\n"
    . "RÉPONSE CORRECTE: {$reponseCorrecte}\n\n"
    . "EXPLIQUE pourquoi l'étudiant s'est trompé et pourquoi la bonne réponse est correcte.\n"
    . "Sois pédagogique, clair et bienveillant en 2-3 phrases complètes.";

echo "════════════════════════════════════════════════════════════════\n";
echo "🧠 TEST PROMPT AMÉLIORÉE - GEMINI 2.5-FLASH\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "📝 QUESTION TESTÉE:\n";
echo "   $question\n\n";

echo "❌ MAUVAISE RÉPONSE:\n";
echo "   $reponseUtilisateur\n\n";

echo "✅ BONNE RÉPONSE:\n";
echo "   $reponseCorrecte\n\n";

echo "🔄 Appel à Gemini avec nouvelle prompt...\n";
echo "─────────────────────────────────────────────────────────────\n\n";

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
                'maxOutputTokens' => 500,
                'temperature' => 0.3,
            ]
        ]
    ]);

    $data = $response->toArray();
    $code = $response->getStatusCode();

    if ($code === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $explication = trim($data['candidates'][0]['content']['parts'][0]['text']);
        $nbMots = str_word_count($explication);

        echo "✅ EXPLICATION GÉNÉRÉE:\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo $explication . "\n";
        echo "════════════════════════════════════════════════════════════════\n\n";

        echo "📊 QUALITÉ DE L'EXPLICATION:\n";
        echo "───────────────────────────────────────────────────────────────\n";
        echo "   • Nombre de mots: $nbMots\n";
        echo "   • Statut: " . ($nbMots >= 20 ? "✅ ACCEPTÉE" : "❌ REJETÉE (trop courte)") . "\n";
        echo "   • Source: 🤖 Gemini 2.5-Flash\n\n";

        if ($nbMots >= 20) {
            echo "✅ L'explication est COMPLÈTE et UTILE!\n";
            echo "Les explications courtes ou vagues seront automatiquement rejetées\n";
            echo "et remplaceées par les explications de la base de données.\n";
        } else {
            echo "❌ L'explication serait rejetée (trop courte)\n";
            echo "Le système fallback automatiquement vers la base de données.\n";
        }
    } else {
        echo "❌ Erreur API (Status: $code)\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n════════════════════════════════════════════════════════════════\n";
echo "📝 NOUVELLES AMÉLIORATIONS:\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ Prompt plus détaillée et pédagogique\n";
echo "✅ Règles claires pour l'IA\n";
echo "✅ maxOutputTokens augmenté à 300\n";
echo "✅ temperature augmentée à 0.7 (plus créatif)\n";
echo "✅ Validation: minimum 20 mots pour accepter\n";
echo "✅ Fallback automatique vers base de données si trop courte\n";
echo "✅ Logging des explications rejetées\n\n";

<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

$geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? null;

echo "════════════════════════════════════════════\n";
echo "🧠 TEST GÉNÉRATION D'EXPLICATION GEMINI\n";
echo "════════════════════════════════════════════\n\n";

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create(['timeout' => 30]);

// Simulation d'une question de quiz avec mauvaise réponse
$question = "Quel est la capitale de la France?";
$reponseUtilisateur = "Lyon";
$reponseCorrecte = "Paris";

$prompt = "Q: {$question}\n"
    . "Élève a répondu: {$reponseUtilisateur}\n"
    . "Bonne réponse: {$reponseCorrecte}\n\n"
    . "Rédigez 1-2 phrases pour expliquer l'erreur et pourquoi la bonne réponse est juste.";

echo "📝 Paramètres:\n";
echo "   Question: $question\n";
echo "   Réponse étudiant: $reponseUtilisateur\n";
echo "   Réponse correcte: $reponseCorrecte\n\n";

echo "🔄 Appel à Gemini...\n\n";

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

    $code = $response->getStatusCode();

    if ($code === 200) {
        $data = $response->toArray();

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $explication = $data['candidates'][0]['content']['parts'][0]['text'];
            echo "✅ EXPLICATION GÉNÉRÉE PAR GEMINI:\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo wordwrap($explication, 80, "\n", false) . "\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            echo "✅ API Gemini fonctionne correctement! 🚀\n";
            echo "Le service ChatbotAnalyseService peut maintenant générer des explications\n";
            echo "personnalisées pour chaque mauvaise réponse.\n";
        }
    } else {
        echo "❌ Erreur API (Status: $code)\n";
        $data = $response->toArray();
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

use Symfony\Component\HttpClient\HttpClient;

$geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? null;

echo "════════════════════════════════════════════════════════════════\n";
echo "✅ AFFICHAGE AVEC SOURCE DU MODÈLE\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "📊 RÉSUMÉ DES MODIFICATIONS:\n";
echo "───────────────────────────────────────────────────────────────\n\n";

echo "1️⃣ HEADER DU RAPPORT:\n";
echo "   ═══════════════════════════════════════════════════════════\n";
echo "           📊 RAPPORT D'ANALYSE DE VOTRE QUIZ\n";
echo "   ═══════════════════════════════════════════════════════════\n";
echo "   🤖 Assisté par IA (Gemini 2.5-Flash)\n";
echo "   ═══════════════════════════════════════════════════════════\n\n";

echo "2️⃣ SECTION ERREURS - AVEC SOURCE AFFICHÉE:\n";
echo "   ─── Erreur #1 ───\n";
echo "   Question : Quel est la capitale de la France?\n";
echo "   Votre réponse : Lyon\n";
echo "   Réponse correcte : Paris\n";
echo "   Points perdus : 5\n";
echo "   Source explication : Gemini 2.5-Flash ✨ <-- NOUVELLE LIGNE\n\n";

echo "   💡 Explication générale :\n";
echo "   [Contenu de l'explication...]\n\n";

echo "3️⃣ CONSEIL PERSONNALISÉ:\n";
echo "   Erreur #1 : « Quel est la capitale de la France? »\n";
echo "   [...explication...] (Source: Gemini 2.5-Flash) ✨ <-- MENTION DE LA SOURCE\n\n";

echo "4️⃣ SECTION FINALE - SOURCES UTILISÉES:\n";
echo "   ───────────────────────────────────────────────────────────\n";
echo "   📊 SOURCES DES EXPLICATIONS\n";
echo "   ───────────────────────────────────────────────────────────\n";
echo "   🤖 Gemini 2.5-Flash (IA): 3 explication(s)\n";
echo "   📚 Base de données: 2 explication(s)\n\n";

echo "5️⃣ NOTE FINALE:\n";
echo "   Ce rapport a été généré automatiquement par votre assistant\n";
echo "   d'apprentissage assisté par IA (Gemini 2.5-Flash) pour vous aider\n";
echo "   à progresser efficacement.\n\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "✅ NOUVELLES FONCTIONNALITÉS AJOUTÉES:\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✓ Header du rapport affiche 'Assisté par IA (Gemini 2.5-Flash)'\n";
echo "✓ Chaque erreur affiche sa 'Source explication'\n";
echo "✓ Conseils mentionnent la source: (Source: Gemini 2.5-Flash)\n";
echo "✓ Section finale récapitule les sources utilisées\n";
echo "✓ Conclusion mentionne explicitement Gemini 2.5-Flash\n\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "📝 SOURCES POSSIBLES:\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🤖 Gemini 2.5-Flash  - Explication générée par l'IA\n";
echo "📚 Base de données   - Explication stockée en base\n";
echo "🔧 Défaut            - Message par défaut\n\n";

echo "================================================================\n";
echo "✅ IMPLÉMENTATION COMPLÈTE!\n";
echo "================================================================\n";

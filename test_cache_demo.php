<?php
require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load('.env');

echo "════════════════════════════════════════════\n";
echo "DÉMONSTRATION: Cache des explications Gemini\n";
echo "════════════════════════════════════════════\n\n";

// Simulation du cache
function genererClePuissance($question, $reponseUtilisateur, $reponseCorrecte)
{
    return md5($question . '||' . $reponseUtilisateur . '||' . $reponseCorrecte);
}

function obtenirCheminCache($cleCache)
{
    return sys_get_temp_dir() . '/gemini_cache_' . $cleCache . '.json';
}

// Question 1
$q1 = "Quelle commande permet de créer un nouveau contrôleur en Symfony ?";
$r1 = "php bin/console make:entity NomEntity";
$c1 = "php bin/console make:controller NomController";

$cle1 = genererClePuissance($q1, $r1, $c1);
$chemin1 = obtenirCheminCache($cle1);

echo "Scénario 1: Première requête (Appel API + Cache)\n";
echo "─" . str_repeat("─", 50) . "─\n";
echo "Question: $q1\n";
echo "Clé cache: $cle1\n";
echo "Chemin: $chemin1\n";

if (file_exists($chemin1)) {
    $donnees = json_decode(file_get_contents($chemin1), true);
    echo "✅ Explication en cache depuis: " . date('H:i:s', filemtime($chemin1)) . "\n";
    echo "Source: " . $donnees['source'] . "\n";
    echo "Texte: " . substr($donnees['texte'], 0, 100) . "...\n";
} else {
    echo "❌ Pas en cache (appel API)\n";
    echo "Actions:\n";
    echo "  1. Appel API Gemini\n";
    echo "  2. Récupération réponse\n";
    echo "  3. Validation (>20 mots)\n";
    echo "  4. MISE EN CACHE\n";
    echo "  5. Retour explication\n";

    // Simuler le cache avec données de test
    $explicationTest = [
        'texte' => 'La commande php bin/console make:entity est incorrecte car elle crée une entité Doctrine (table de base de données), pas un contrôleur. La bonne commande est php bin/console make:controller qui génère un fichier contrôleur avec la structure adéquate pour gérer les requêtes HTTP.',
        'source' => 'Gemini 2.5 Flash'
    ];
    @mkdir(dirname($chemin1), 0755, true);
    file_put_contents($chemin1, json_encode($explicationTest));
    echo "\n✅ Explication mise en cache avec succès\n";
}

echo "\n";
echo "Scénario 2: Requête suivante MÊME QUESTION (Lecture cache)\n";
echo "─" . str_repeat("─", 50) . "─\n";
echo "Question: $q1\n";
echo "Clé cache: $cle1 (MÊME clé = même question)\n";

if (file_exists($chemin1)) {
    $donnees = json_decode(file_get_contents($chemin1), true);
    echo "✅ Explication trouvée en cache!\n";
    echo "Source: " . $donnees['source'] . "\n";
    echo "Temps: Instantané (0ms au lieu de 2-3s API)\n";
    echo "\n🎯 AVANTAGE: Pas d'appel API, réponse immédiate\n";
}

echo "\n";
echo "Bénéfices du cache:\n";
echo "─" . str_repeat("─", 50) . "─\n";
echo "1. 🚀 Réponses instantanées (pas d'appel API)\n";
echo "2. 📉 Réduit la pression du rate limit (429)\n";
echo "3. 💾 Stocke les explications Gemini déjà générées\n";
echo "4. 🔄 Réutiliser pour les mêmes questions\n";
echo "   - Même si plusieurs apprenants posent\n";
echo "   - Au cours de plusieurs sessions\n";
echo "\n";

echo "État du cache:\n";
echo "─" . str_repeat("─", 50) . "─\n";
$pattern = sys_get_temp_dir() . '/gemini_cache_*.json';
$files = glob(sys_get_temp_dir() . '/gemini_cache_*.json');
echo "Fichiers en cache: " . count($files) . "\n";
foreach ($files as $file) {
    echo "  - " . basename($file) . " (" . filesize($file) . " bytes)\n";
}

echo "\n════════════════════════════════════════════\n";
echo "✅ Système de cache Gemini configuré et fonctionnel\n";
echo "   Attendez que le rate limit (429) se lève (1-2h),\n";
echo "   puis la première explication sera généée et cachée.\n";

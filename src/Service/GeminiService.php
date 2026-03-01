<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service pour générer des questions de quiz via l'API Google Gemini.
 */
class GeminiService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null
    ) {
        if ($this->apiKey === null) {
            throw new \RuntimeException('La clé GEMINI_API_KEY n\'est pas définie.');
        }
    }

    /**
     * Génère des questions de quiz structurées via Gemini.
     *
     * @param string $sujet        Le sujet ou contexte du quiz
     * @param int    $nombre       Nombre de questions à générer
     * @param array  $types        Types souhaités : ['qcm', 'vrai_faux', 'texte']
     * @param int    $pointsDefaut Points par défaut par question
     *
     * @return array Liste de questions structurées
     * @throws \Exception En cas d'erreur API ou de parsing JSON
     */
    public function generateQuestions(
        string $sujet,
        int $nombre = 5,
        array $types = ['qcm'],
        int $pointsDefaut = 1
    ): array {
        $typesStr = implode(', ', $types);

        $prompt = <<<PROMPT
Tu es un expert pédagogique. Génère exactement {$nombre} questions de quiz sur le sujet suivant :

**Sujet :** {$sujet}

**Types de questions autorisés :** {$typesStr}

**Instructions strictes :**
- Réponds UNIQUEMENT avec un tableau JSON valide, sans texte avant ni après.
- Ne mets PAS de balises markdown (pas de ```json ni ```).
- Chaque question doit avoir exactement ces champs :

Pour QCM :
{
  "enonce": "Texte de la question ?",
  "type": "qcm",
  "points": {$pointsDefaut},
  "explication": "Explication courte de la bonne réponse",
  "reponses": [
    {"texte": "Option A", "estCorrecte": false},
    {"texte": "Option B", "estCorrecte": true},
    {"texte": "Option C", "estCorrecte": false},
    {"texte": "Option D", "estCorrecte": false}
  ]
}

Pour Vrai/Faux :
{
  "enonce": "Affirmation à évaluer.",
  "type": "vrai_faux",
  "points": {$pointsDefaut},
  "explication": "Explication de la réponse correcte",
  "reponses": [
    {"texte": "Vrai", "estCorrecte": true},
    {"texte": "Faux", "estCorrecte": false}
  ]
}

Pour Texte libre :
{
  "enonce": "Question ouverte ?",
  "type": "texte",
  "points": {$pointsDefaut},
  "explication": "Éléments de réponse attendus",
  "reponses": []
}

Génère maintenant exactement {$nombre} question(s) de type(s) : {$typesStr}.
Assure-toi que chaque QCM a exactement 1 bonne réponse parmi 4 options.
PROMPT;

        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'query' => [
                'key' => $this->apiKey,
            ],
            'json' => [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 4096,
                ],
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode === 429) {
            throw new \RuntimeException("Le quota de l'API Gemini est dépassé.");
        }
        if ($statusCode !== 200) {
            throw new \RuntimeException("Erreur API Gemini (HTTP {$statusCode}) : " . $response->getContent(false));
        }

        $data = $response->toArray();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return $this->parseJsonResponse($text);
    }

    /**
     * Parse la réponse JSON de Gemini et la nettoie si nécessaire.
     */
    private function parseJsonResponse(string $text): array
    {
        // Nettoyage : supprimer les éventuels blocs markdown
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```\s*$/', '', $text);
        $text = trim($text);

        $questions = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Tentative : chercher un tableau JSON dans le texte
            if (preg_match('/\[.*\]/s', $text, $matches)) {
                $questions = json_decode($matches[0], true);
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException(
                    'Impossible de parser la réponse JSON de Gemini. Erreur : ' . json_last_error_msg()
                );
            }
        }

        if (!is_array($questions)) {
            throw new \RuntimeException('La réponse Gemini n\'est pas un tableau JSON valide.');
        }

        return array_map([$this, 'normalizeQuestion'], $questions);
    }

    /**
     * Normalise et valide une question générée.
     */
    private function normalizeQuestion(array $q): array
    {
        $typesValides = ['qcm', 'vrai_faux', 'texte'];

        return [
            'enonce'      => trim($q['enonce'] ?? 'Question sans énoncé'),
            'type'        => in_array($q['type'] ?? '', $typesValides, true) ? $q['type'] : 'qcm',
            'points'      => max(1, min(100, (int)($q['points'] ?? 1))),
            'explication' => trim($q['explication'] ?? ''),
            'reponses'    => array_map(
                fn($r) => [
                    'texte'      => trim($r['texte'] ?? ''),
                    'estCorrecte' => (bool)($r['estCorrecte'] ?? false),
                ],
                $q['reponses'] ?? []
            ),
        ];
    }
}

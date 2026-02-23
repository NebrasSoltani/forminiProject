<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null  // ← clé nullable
    ) {
        if ($this->apiKey === null) {
            throw new \RuntimeException('La clé GEMINI_API_KEY n’est pas définie.');
        }
    }

    public function generateQuestions(string $sujet, int $nombre = 5, array $types = ['qcm'], int $pointsDefaut = 1): array
    {
        $typesStr = implode(', ', $types);
        $prompt = <<<PROMPT
Tu es un expert pédagogique. Génère exactement {$nombre} questions de quiz sur le sujet suivant :

**Sujet :** {$sujet}

**Types de questions autorisés :** {$typesStr}

... (le reste de ton prompt)
PROMPT;

        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => ['Content-Type' => 'application/json'],
            'query'   => ['key' => $this->apiKey],
            'json'    => [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 4096],
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
        if (empty($text)) {
            throw new \RuntimeException('Gemini n\'a retourné aucun contenu.');
        }

        return $this->parseJsonResponse($text);
    }

    private function parseJsonResponse(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```\s*$/', '', $text));
        $questions = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE && preg_match('/\[.*\]/s', $text, $matches)) {
            $questions = json_decode($matches[0], true);
        }

        if (!is_array($questions)) {
            throw new \RuntimeException('La réponse Gemini n\'est pas un tableau JSON valide.');
        }

        return array_map([$this, 'normalizeQuestion'], $questions);
    }

    private function normalizeQuestion(array $q): array
    {
        $typesValides = ['qcm', 'vrai_faux', 'texte'];
        return [
            'enonce'      => trim($q['enonce'] ?? 'Question sans énoncé'),
            'type'        => in_array($q['type'] ?? '', $typesValides, true) ? $q['type'] : 'qcm',
            'points'      => max(1, min(100, (int)($q['points'] ?? 1))),
            'explication' => trim($q['explication'] ?? ''),
            'reponses'    => array_map(fn($r) => [
                'texte'      => trim($r['texte'] ?? ''),
                'estCorrecte'=> (bool)($r['estCorrecte'] ?? false),
            ], $q['reponses'] ?? []),
        ];
    }
}
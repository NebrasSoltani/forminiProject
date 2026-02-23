<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Repository\BlogRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AiRecommendationService
{
    private HttpClientInterface $client;
    private BlogRepository $blogRepository;
    private LoggerInterface $logger;
    private string $groqApiKey;

    public function __construct(
        HttpClientInterface $client,
        BlogRepository $blogRepository,
        LoggerInterface $logger,
        string $groqApiKey = ''
    ) {
        $this->client = $client;
        $this->blogRepository = $blogRepository;
        $this->logger = $logger;
        $this->groqApiKey = $groqApiKey;
    }

    public function recommendBlogs(Evenement $evenement, string $userQuery = null): array
    {
        // On récupère les 20 derniers blogs pour avoir plus de choix
        $candidateBlogs = $this->blogRepository->findBy([], ['datePublication' => 'DESC'], 20);

        $blogsData = [];
        foreach ($candidateBlogs as $blog) {
            $blogsData[] = [
                'id' => $blog->getId(),
                'title' => $blog->getTitre(),
                'summary' => substr(strip_tags($blog->getContenu() ?? ''), 0, 150) . '...'
            ];
        }

        if (empty($this->groqApiKey)) {
            return ['error' => 'Clé API non configurée.'];
        }

        $context = sprintf(
            "Événement: %s\nDescription: %s\nFilières: %s\nTags: %s",
            $evenement->getTitre(),
            $evenement->getDescription(),
            implode(', ', $evenement->getFilieres() ?? []),
            implode(', ', $evenement->getTags() ?? [])
        );

        $prompt = "Tu es un expert en marketing et création de contenu. Ton but est d'aider l'organisateur d'un événement à créer des articles de blog pour promouvoir son événement.

        CONTEXTE DE L'ÉVÉNEMENT :
        {$context}

        REQUETE UTILISATEUR :
        " . ($userQuery ?: "Génère-moi des idées d'articles de blog innovants pour promouvoir cet événement.") . "

        CONSIGNES DE RÉPONSE (CRITIQUE) :
        1. Langue : **Français exclusivement**.
        2. Style : **Ultra-concis, direct et percutant**. Pas de longs paragraphes vagues.
        3. Formatage : Utilise le **gras (Markdown)** pour souligner les points clés.
        4. Mission : Génère 3 idées d'articles de blog NOUVEAUX (Titre court en gras, Catégorie, Résumé de 2 phrases max).
        5. Structure de la réponse `answer` : Très courte (1-2 lignes max), utilisant le gras pour le message principal.

        Réponds EXCLUSIVEMENT au format JSON comme ceci :
        {
          \"answer\": \"**Texte court et clair** en gras...\",
          \"generated_ideas\": [
            {
              \"title\": \"Titre en gras\",
              \"category\": \"Catégorie\",
              \"summary\": \"Résumé direct et court.\"
            }
          ],
          \"existing_recommendations\": []
        }";

        try {
            $response = $this->client->request('POST',
                'https://api.groq.com/openai/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->groqApiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'llama-3.3-70b-versatile',
                        'messages' => [
                            ['role' => 'system', 'content' => "Tu es un expert en content marketing minimaliste. Tu réponds toujours en JSON. Tes réponses sont courtes, claires et utilisent le gras pour l'emphase."],
                            ['role' => 'user', 'content' => $prompt]
                        ],
                        'temperature' => 0.6,
                        'max_tokens' => 1000,
                    ]
                ]
            );

            $content = $response->toArray();
            if (isset($content['choices'][0]['message']['content'])) {
                $aiJson = trim($content['choices'][0]['message']['content']);
                return $this->parseAiResponse($aiJson, $candidateBlogs);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur Groq: ' . $e->getMessage());
        }

        return ['error' => 'Service temporairement indisponible.'];
    }

    private function parseAiResponse(string $text, array $allBlogs): array
    {
        $text = preg_replace('/^```json\s*|\s*```$/m', '', $text);
        $data = json_decode($text, true);

        if (!$data) {
            return ['answer' => "**Erreur de formatage**. Réponse brute : " . $text];
        }

        $formatted = [];
        if (!empty($data['existing_recommendations'])) {
            foreach ($data['existing_recommendations'] as $item) {
                $blog = $this->findBlogById($allBlogs, $item['blog_id']);
                if ($blog) {
                    $formatted[] = [
                        'blog' => [
                            'id' => $blog->getId(),
                            'titre' => $blog->getTitre(),
                            'image' => $blog->getImage(),
                            'slug' => method_exists($blog, 'getSlug') ? $blog->getSlug() : $blog->getId()
                        ],
                        'reason' => $item['reason']
                    ];
                }
            }
        }

        return [
            'answer' => $data['answer'] ?? "**Voici vos idées :**",
            'generated_ideas' => $data['generated_ideas'] ?? [],
            'recommendations' => $formatted
        ];
    }

    private function findBlogById(array $blogs, $id)
    {
        foreach ($blogs as $blog) {
            if ((string)$blog->getId() === (string)$id) {
                return $blog;
            }
        }
        return null;
    }
}

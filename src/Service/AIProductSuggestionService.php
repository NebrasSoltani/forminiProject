<?php

namespace App\Service;

use App\Entity\CommandeItem;
use App\Entity\Produit;
use App\Entity\User;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIProductSuggestionService
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';

    public function __construct(
        private readonly ProduitRepository $produitRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly ?string $geminiApiKey = null
    ) {
    }

    /**
     * @return Produit[]
     */
    public function suggestForProduct(Produit $currentProduct, ?User $user, int $limit = 4): array
    {
        $limit = max(1, min($limit, 8));
        $candidates = $this->loadSameTypeCandidateProducts($currentProduct);

        if ($candidates === []) {
            $candidates = $this->loadOtherTypeCandidateProducts($currentProduct);
            if ($candidates === []) {
                return [];
            }
        }

        $categoryPreferences = $user ? $this->loadUserCategoryPreferences($user) : [];

        $orderedByFallback = $this->rankByFallback($candidates, $currentProduct, $categoryPreferences);
        $orderedByAi = $this->rankByAi($candidates, $currentProduct, $categoryPreferences);

        $result = [];
        $seen = [];

        foreach (array_merge($orderedByAi, $orderedByFallback) as $product) {
            $id = $product->getId();
            if ($id === null || isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $result[] = $product;

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * @return Produit[]
     */
    private function loadSameTypeCandidateProducts(Produit $currentProduct): array
    {
        return $this->produitRepository->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->andWhere('p.stock > 0')
            ->andWhere('p.id != :currentId')
            ->andWhere('p.categorie = :categorie')
            ->setParameter('statut', 'actif')
            ->setParameter('currentId', $currentProduct->getId())
            ->setParameter('categorie', $currentProduct->getCategorie())
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Produit[]
     */
    private function loadOtherTypeCandidateProducts(Produit $currentProduct): array
    {
        return $this->produitRepository->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->andWhere('p.stock > 0')
            ->andWhere('p.id != :currentId')
            ->andWhere('p.categorie != :categorie')
            ->setParameter('statut', 'actif')
            ->setParameter('currentId', $currentProduct->getId())
            ->setParameter('categorie', $currentProduct->getCategorie())
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int>
     */
    private function loadUserCategoryPreferences(User $user): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('p.categorie AS categorie, SUM(ci.quantite) AS totalQty')
            ->from(CommandeItem::class, 'ci')
            ->innerJoin('ci.commande', 'c')
            ->innerJoin('ci.produit', 'p')
            ->where('c.utilisateur = :user')
            ->setParameter('user', $user)
            ->groupBy('p.categorie')
            ->getQuery()
            ->getArrayResult();

        $preferences = [];
        foreach ($rows as $row) {
            $category = (string) ($row['categorie'] ?? '');
            if ($category === '') {
                continue;
            }

            $preferences[$category] = (int) ($row['totalQty'] ?? 0);
        }

        return $preferences;
    }

    /**
     * @param Produit[] $candidates
     * @param array<string, int> $categoryPreferences
     * @return Produit[]
     */
    private function rankByFallback(array $candidates, Produit $currentProduct, array $categoryPreferences): array
    {
        $currentCategory = $currentProduct->getCategorie();

        usort($candidates, function (Produit $a, Produit $b) use ($currentCategory, $categoryPreferences): int {
            $scoreA = $this->computeFallbackScore($a, $currentCategory, $categoryPreferences);
            $scoreB = $this->computeFallbackScore($b, $currentCategory, $categoryPreferences);

            if ($scoreA === $scoreB) {
                return ($b->getId() ?? 0) <=> ($a->getId() ?? 0);
            }

            return $scoreB <=> $scoreA;
        });

        return $candidates;
    }

    /**
     * @param array<string, int> $categoryPreferences
     */
    private function computeFallbackScore(Produit $product, ?string $currentCategory, array $categoryPreferences): int
    {
        $score = 0;
        $category = $product->getCategorie();

        if ($currentCategory !== null && $currentCategory !== '' && $category === $currentCategory) {
            $score += 100;
        }

        $score += ($category !== null && isset($categoryPreferences[$category])) ? ($categoryPreferences[$category] * 10) : 0;
        $score += max(0, min(50, $product->getStock() ?? 0));

        return $score;
    }

    /**
     * @param Produit[] $candidates
     * @param array<string, int> $categoryPreferences
     * @return Produit[]
     */
    private function rankByAi(array $candidates, Produit $currentProduct, array $categoryPreferences): array
    {
        if (!$this->geminiApiKey) {
            return [];
        }

        try {
            $payloadCandidates = array_map(static function (Produit $product): array {
                return [
                    'id' => $product->getId(),
                    'nom' => $product->getNom(),
                    'categorie' => $product->getCategorie(),
                    'prix' => (float) $product->getPrix(),
                    'stock' => $product->getStock(),
                ];
            }, $candidates);

            $prompt = "Tu es un moteur de recommandation e-commerce.\n" .
                "Choisis les meilleurs produits a recommander pour cet apprenant.\n" .
                "Renvoie uniquement un JSON valide sous cette forme: {\"product_ids\":[1,2,3,4]}.\n" .
                "N'ajoute aucun texte hors JSON.\n\n" .
                "Produit courant:\n" . json_encode([
                    'id' => $currentProduct->getId(),
                    'nom' => $currentProduct->getNom(),
                    'categorie' => $currentProduct->getCategorie(),
                    'prix' => (float) $currentProduct->getPrix(),
                ], JSON_UNESCAPED_UNICODE) . "\n\n" .
                "Preferences categories de l'utilisateur:\n" . json_encode($categoryPreferences, JSON_UNESCAPED_UNICODE) . "\n\n" .
                "Produits candidats:\n" . json_encode($payloadCandidates, JSON_UNESCAPED_UNICODE);

            $response = $this->httpClient->request('POST', self::GEMINI_API_URL, [
                'query' => ['key' => $this->geminiApiKey],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 300,
                    ],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning('AI product suggestions skipped: Gemini HTTP ' . $response->getStatusCode());
                return [];
            }

            $data = $response->toArray(false);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!is_string($text) || trim($text) === '') {
                return [];
            }

            $decoded = $this->decodeJson($text);
            if (!is_array($decoded)) {
                return [];
            }

            $ids = $decoded['product_ids'] ?? $decoded;
            if (!is_array($ids)) {
                return [];
            }

            $map = [];
            foreach ($candidates as $candidate) {
                $candidateId = $candidate->getId();
                if ($candidateId !== null) {
                    $map[$candidateId] = $candidate;
                }
            }

            $ordered = [];
            foreach ($ids as $id) {
                $id = (int) $id;
                if ($id > 0 && isset($map[$id])) {
                    $ordered[] = $map[$id];
                }
            }

            return $ordered;
        } catch (\Throwable $e) {
            $this->logger->warning('AI product suggestions failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @return array<mixed>|null
     */
    private function decodeJson(string $raw): ?array
    {
        $clean = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($raw)));
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $clean, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}

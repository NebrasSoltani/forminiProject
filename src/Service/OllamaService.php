<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class OllamaService
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private string $ollamaUrl = 'http://localhost:11434/api/generate';

    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param string $prompt
     * @param string $model Defaults to mistral, can be changed to tinyllama if RAM is low
     * @return string
     */
    public function generateSummary(string $prompt, string $model = 'mistral'): string
    {
        try {
            $response = $this->client->request('POST', $this->ollamaUrl, [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false
                ],
                'timeout' => 30 // As requested, timeout after 30s
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Ollama API error: Status ' . $response->getStatusCode());
                return '';
            }

            $content = $response->toArray();
            return $content['response'] ?? '';

        } catch (\Exception $e) {
            $this->logger->error('Ollama communication error: ' . $e->getMessage());
            return '';
        }
    }
}

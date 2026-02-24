<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TurnstileService
{
    private HttpClientInterface $client;
    private string $secretKey;
    private string $siteKey;

    public function __construct(HttpClientInterface $client, ParameterBagInterface $parameterBag)
    {
        $this->client = $client;
        $this->secretKey = $parameterBag->get('app.turnstile_secret_key');
        $this->siteKey = $parameterBag->get('app.turnstile_site_key');
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function verify(string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            $response = $this->client->request('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ],
            ]);

            $data = $response->toArray();
            
            return $data['success'] ?? false;
        } catch (\Exception $e) {
            // Log error if needed
            return false;
        }
    }
}

<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Exception\TransportException;

class CallMeBotWhatsappSender
{
    private string $apiKey;
    private string $defaultCountryCode;

    public function __construct(
        ?string $callmebotApiKey = null,
        ?string $smsDefaultCountryCode = '+216'
    ) {
        $this->apiKey = $callmebotApiKey ?? '';
        $this->defaultCountryCode = $smsDefaultCountryCode ?: '+216';
    }

    public function send(string $toPhone, string $message): void
    {
        if ($this->apiKey === '') {
            throw new TransportException('CallMeBot API key is not configured.');
        }

        $normalizedPhone = $this->normalizePhone($toPhone);
        if ($normalizedPhone === null) {
            throw new TransportException('Invalid destination phone number.');
        }

        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.callmebot.com/whatsapp.php', [
            'query' => [
                'phone' => $normalizedPhone,
                'text' => $message,
                'apikey' => $this->apiKey,
            ],
            'headers' => [
                'Accept' => 'text/plain',
            ],
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new TransportException(sprintf(
                'CallMeBot API error (%d): %s',
                $status,
                $response->getContent(false)
            ));
        }
    }

    private function normalizePhone(string $phone): ?string
    {
        $clean = preg_replace('/[^\d+]/', '', trim($phone));
        if ($clean === null || $clean === '') {
            return null;
        }

        if (str_starts_with($clean, '+')) {
            return substr($clean, 1);
        }

        if (str_starts_with($clean, '00')) {
            return substr($clean, 2);
        }

        if (str_starts_with($clean, '0')) {
            return ltrim($this->defaultCountryCode, '+') . substr($clean, 1);
        }

        if (preg_match('/^\d{8}$/', $clean) === 1) {
            return ltrim($this->defaultCountryCode, '+') . $clean;
        }

        if (preg_match('/^\d{10,15}$/', $clean) === 1) {
            return $clean;
        }

        return null;
    }
}

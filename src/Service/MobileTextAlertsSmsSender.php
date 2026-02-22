<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Exception\TransportException;

class MobileTextAlertsSmsSender
{
    private string $apiUrl;
    private string $apiKey;
    private string $sender;
    private string $defaultCountryCode;
    private ?int $templateId;

    public function __construct(
        ?string $mobileTextAlertsApiUrl = null,
        ?string $mobileTextAlertsApiKey = null,
        ?string $mobileTextAlertsSender = null,
        ?string $smsDefaultCountryCode = '+216',
        ?string $mobileTextAlertsTemplateId = null
    ) {
        $this->apiUrl = $mobileTextAlertsApiUrl ?? '';
        $this->apiKey = $mobileTextAlertsApiKey ?? '';
        $this->sender = $mobileTextAlertsSender ?? '';
        $this->defaultCountryCode = $smsDefaultCountryCode ?: '+216';
        $this->templateId = is_numeric($mobileTextAlertsTemplateId) ? (int) $mobileTextAlertsTemplateId : null;
    }

    public function send(string $phone, string $message): void
    {
        if ($this->apiUrl === '' || $this->apiKey === '') {
            throw new TransportException('Mobile Text Alerts API is not configured.');
        }

        $to = $this->normalizePhone($phone);
        if ($to === null) {
            throw new TransportException('Invalid destination phone number.');
        }

        $client = HttpClient::create();
        if ($this->templateId !== null && $this->templateId > 0) {
            $this->assertTemplateExists($client, $this->templateId);
        }

        $payload = [
            'subscribers' => [$to],
        ];

        if ($this->templateId !== null && $this->templateId > 0) {
            $payload['templateId'] = $this->templateId;
        } else {
            $payload['message'] = $message;
        }

        $response = $client->request('POST', $this->apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new TransportException(sprintf(
                'Mobile Text Alerts API error (%d): %s',
                $status,
                $response->getContent(false)
            ));
        }
    }

    private function assertTemplateExists($client, int $templateId): void
    {
        $host = parse_url($this->apiUrl, PHP_URL_SCHEME) . '://' . parse_url($this->apiUrl, PHP_URL_HOST);
        $url = rtrim((string) $host, '/') . '/v3/controlled-templates/' . $templateId;

        $response = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ],
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new TransportException(sprintf(
                'Mobile Text Alerts template check failed (%d): %s',
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
            return $clean;
        }

        if (str_starts_with($clean, '00')) {
            return '+' . substr($clean, 2);
        }

        if (str_starts_with($clean, '0')) {
            return $this->defaultCountryCode . substr($clean, 1);
        }

        if (preg_match('/^\d{8}$/', $clean) === 1) {
            return $this->defaultCountryCode . $clean;
        }

        if (preg_match('/^\d{10,15}$/', $clean) === 1) {
            return '+' . $clean;
        }

        return null;
    }
}

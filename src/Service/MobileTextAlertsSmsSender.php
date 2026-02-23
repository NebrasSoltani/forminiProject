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
            // Best-effort check only: some accounts return 404 here but still allow send endpoint.
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
        if ($status >= 200 && $status < 300) {
            return;
        }

        $errorBody = $response->getContent(false);

        // Unverified MTA accounts can only send template messages.
        // If no template is configured, try to auto-detect one and retry once.
        if (
            $status === 403
            && $this->templateId === null
            && str_contains($errorBody, 'only can send template messages')
        ) {
            $autoTemplateId = $this->discoverGlobalTemplateId($client);
            if ($autoTemplateId !== null) {
                $retryPayload = [
                    'subscribers' => [$to],
                    'templateId' => $autoTemplateId,
                ];

                $retryResponse = $client->request('POST', $this->apiUrl, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $retryPayload,
                ]);

                $retryStatus = $retryResponse->getStatusCode();
                if ($retryStatus >= 200 && $retryStatus < 300) {
                    return;
                }

                throw new TransportException(sprintf(
                    'Mobile Text Alerts API error (%d) after template retry: %s',
                    $retryStatus,
                    $retryResponse->getContent(false)
                ));
            }

            throw new TransportException(
                'Mobile Text Alerts account requires a template. Configure MOBILE_TEXT_ALERTS_TEMPLATE_ID in .env.'
            );
        }

        throw new TransportException(sprintf(
            'Mobile Text Alerts API error (%d): %s',
            $status,
            $errorBody
        ));
    }

    private function assertTemplateExists($client, int $templateId): bool
    {
        $host = parse_url($this->apiUrl, PHP_URL_SCHEME) . '://' . parse_url($this->apiUrl, PHP_URL_HOST);
        $url = rtrim((string) $host, '/') . '/v3/controlled-templates/' . $templateId;

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $status = $response->getStatusCode();
            return $status >= 200 && $status < 300;
        } catch (\Throwable) {
            return false;
        }
    }

    private function discoverGlobalTemplateId($client): ?int
    {
        $host = parse_url($this->apiUrl, PHP_URL_SCHEME) . '://' . parse_url($this->apiUrl, PHP_URL_HOST);
        $url = rtrim((string) $host, '/') . '/v3/controlled-templates/global';

        $response = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ],
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return null;
        }

        $data = json_decode($response->getContent(false), true);
        if (!is_array($data)) {
            return null;
        }

        $rows = $data['data']['rows'] ?? $data['data'] ?? [];
        if (!is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            $id = $row['id'] ?? null;
            if (is_numeric($id)) {
                return (int) $id;
            }
        }

        return null;
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

<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Exception\TransportException;

class TwilioSmsSender
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private string $defaultCountryCode;
    private string $apiKeySid;
    private string $apiKeySecret;

    public function __construct(
        ?string $twilioAccountSid = null,
        ?string $twilioAuthToken = null,
        ?string $twilioFromNumber = null,
        ?string $smsDefaultCountryCode = '+216',
        ?string $twilioApiKeySid = null,
        ?string $twilioApiKeySecret = null
    ) {
        $this->accountSid = $twilioAccountSid ?? '';
        $this->authToken = $twilioAuthToken ?? '';
        $this->fromNumber = $twilioFromNumber ?? '';
        $this->defaultCountryCode = $smsDefaultCountryCode ?: '+216';
        $this->apiKeySid = $twilioApiKeySid ?? '';
        $this->apiKeySecret = $twilioApiKeySecret ?? '';
    }

    public function send(string $toPhone, string $message): void
    {
        if ($this->accountSid === '' || $this->fromNumber === '') {
            throw new TransportException('Twilio SMS is not configured.');
        }

        $username = $this->authToken !== '' ? $this->accountSid : $this->apiKeySid;
        $password = $this->authToken !== '' ? $this->authToken : $this->apiKeySecret;
        if ($username === '' || $password === '') {
            throw new TransportException('Twilio SMS credentials are missing.');
        }

        $normalizedPhone = $this->normalizePhone($toPhone);
        if ($normalizedPhone === null) {
            throw new TransportException('Invalid destination phone number.');
        }

        $client = HttpClient::create();
        $response = $client->request(
            'POST',
            sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $this->accountSid),
            [
                'auth_basic' => [$username, $password],
                'body' => [
                    'From' => $this->fromNumber,
                    'To' => $normalizedPhone,
                    'Body' => $message,
                ],
            ]
        );

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $body = $response->getContent(false);
            throw new TransportException(sprintf('Twilio API error (%d): %s', $status, $body));
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

        if (preg_match('/^\d{8,15}$/', $clean) === 1) {
            return $this->defaultCountryCode . $clean;
        }

        return null;
    }
}

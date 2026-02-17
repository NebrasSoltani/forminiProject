<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SendGridEmailSender
{
    private string $apiKey;
    private string $fromEmail;
    private ?string $fromName;
    private HttpClientInterface $httpClient;

    public function __construct(
        string $sendgridApiKey,
        string $sendgridFromEmail,
        ?string $sendgridFromName = null,
        ?HttpClientInterface $httpClient = null
    ) {
        $this->apiKey = $sendgridApiKey;
        $this->fromEmail = $sendgridFromEmail;
        $this->fromName = $sendgridFromName;
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function send(Email $email): void
    {
        if ($this->apiKey === '') {
            throw new TransportException('SENDGRID_API_KEY is not configured.');
        }

        if (empty($this->apiKey)) {
            throw new TransportException('❌ SendGrid API Key is empty!');
        }

        $from = $email->getFrom();
        $fromAddress = $from[0] ?? new Address($this->fromEmail, $this->fromName ?? '');

        $to = $email->getTo();
        if ($to === []) {
            throw new TransportException('Email has no recipients.');
        }

        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();

        $contents = [];
        if ($textBody !== null && $textBody !== '') {
            $contents[] = ['type' => 'text/plain', 'value' => $textBody];
        }
        if ($htmlBody !== null && $htmlBody !== '') {
            $contents[] = ['type' => 'text/html', 'value' => $htmlBody];
        }
        if ($contents === []) {
            throw new TransportException('Email has no body.');
        }

        $personalizations = [
            [
                'to' => array_map(static fn(Address $a) => ['email' => $a->getAddress(), 'name' => $a->getName()], $to),
                'subject' => $email->getSubject() ?? '',
            ],
        ];

        $payload = [
            'personalizations' => $personalizations,
            'from' => ['email' => $fromAddress->getAddress(), 'name' => $fromAddress->getName()],
            'content' => $contents,
        ];

        try {
            $response = $this->httpClient->request('POST', 'https://api.sendgrid.com/v3/mail/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . trim($this->apiKey),
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 30,
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $body = $response->getContent(false);
                throw new TransportException(sprintf('❌ SendGrid API error (%d): %s', $status, $body));
            }
        } catch (TransportException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TransportException('❌ SendGrid request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function createEmail(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody = null): Email
    {
        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName ?? ''))
            ->to(new Address($toEmail, $toName))
            ->subject($subject)
            ->html($htmlBody);

        if ($textBody !== null) {
            $email->text($textBody);
        }

        return $email;
    }
}

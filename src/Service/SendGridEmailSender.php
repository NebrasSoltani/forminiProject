<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SendGridEmailSender
{
    private string $apiKey;
    private string $fromEmail;
    private ?string $fromName;

    public function __construct(string $sendgridApiKey, string $sendgridFromEmail, ?string $sendgridFromName = null)
    {
        $this->apiKey = $sendgridApiKey;
        $this->fromEmail = $sendgridFromEmail;
        $this->fromName = $sendgridFromName;
    }

    public function send(Email $email): void
    {
        if ($this->apiKey === '') {
            throw new TransportException('SENDGRID_API_KEY is not configured.');
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
                'to' => array_map(static fn (Address $a) => ['email' => $a->getAddress(), 'name' => $a->getName()], $to),
                'subject' => $email->getSubject() ?? '',
            ],
        ];

        $payload = [
            'personalizations' => $personalizations,
            'from' => ['email' => $fromAddress->getAddress(), 'name' => $fromAddress->getName()],
            'content' => $contents,
        ];

        $client = HttpClient::create();
        $response = $client->request('POST', 'https://api.sendgrid.com/v3/mail/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $body = $response->getContent(false);
            throw new TransportException(sprintf('SendGrid API error (%d): %s', $status, $body));
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

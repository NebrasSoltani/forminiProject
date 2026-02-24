<?php

namespace App\Service;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Api\TransactionalSMSApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class BrevoService
{
    private $apiKey;
    private $emailApi;
    private $smsApi;
    private $sender;
    private $fromEmail;
    private $fromName;
    private $logger;

    public function __construct(
        ?string $brevo_api_key,
        ?string $brevo_sms_sender,
        ?string $brevo_from_email,
        ?string $brevo_from_name,
        LoggerInterface $logger
    ) {
        $brevo_api_key = $brevo_api_key ?? '';
        $brevo_sms_sender = $brevo_sms_sender ?? '';
        $brevo_from_email = $brevo_from_email ?? '';
        $brevo_from_name = $brevo_from_name ?? '';

        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', $brevo_api_key);

        $client = new Client();

        $this->emailApi = new TransactionalEmailsApi($client, $config);
        $this->smsApi = new TransactionalSMSApi($client, $config);

        $this->apiKey = $brevo_api_key;
        $this->sender = $brevo_sms_sender;
        $this->fromEmail = $brevo_from_email;
        $this->fromName = $brevo_from_name;
        $this->logger = $logger;
    }

    // 📧 SEND EMAIL
    public function sendEmail($toEmail, $toName, $subject, $content)
    {
        $this->logger->info("BrevoService::sendEmail - Sending to $toEmail");
        
        $email = new \Brevo\Client\Model\SendSmtpEmail([
            'subject' => $subject,
            'sender' => ['email' => $this->fromEmail, 'name' => $this->fromName],
            'to' => [
                ['email' => $toEmail, 'name' => $toName]
            ],
            'htmlContent' => $content
        ]);

        try {
            $this->emailApi->sendTransacEmail($email);
            $this->logger->info("BrevoService::sendEmail - Success to $toEmail");
        } catch (\Exception $e) {
            $this->logger->error("BrevoService::sendEmail - Failed: " . $e->getMessage());
            throw $e;
        }
    }

    // 📱 SEND SMS
    public function sendSMS($phone, $message)
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('BREVO_API_KEY is not configured.');
        }

        if ($this->sender === '') {
            throw new \RuntimeException('BREVO_SMS_SENDER is not configured.');
        }

        $phone = $this->normalizePhone((string) $phone);
        if ($phone === null) {
            throw new \RuntimeException('Invalid phone number format for SMS.');
        }

        $this->logger->info("BrevoService::sendSMS - Sending to $phone");
        
        $sms = new \Brevo\Client\Model\SendTransacSms([
            'sender' => $this->sender,
            'recipient' => $phone,
            'content' => $message,
            'type' => 'transactional'
        ]);

        try {
            $this->smsApi->sendTransacSms($sms);
            $this->logger->info("BrevoService::sendSMS - Success to $phone");
        } catch (\Exception $e) {
            $this->logger->error("BrevoService::sendSMS - Failed: " . $e->getMessage());
            throw $e;
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

        if (preg_match('/^\d{8}$/', $clean) === 1) {
            return '+216' . $clean;
        }

        if (preg_match('/^\d{10,15}$/', $clean) === 1) {
            return '+' . $clean;
        }

        return null;
    }
}

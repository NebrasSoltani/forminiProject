<?php

namespace App\Service;

use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;

class TwilioVerifyService
{
    private Client $twilio;
    private string $verifyServiceSid;
    private LoggerInterface $logger;

    public function __construct(
        string $accountSid,
        string $authToken,
        string $verifyServiceSid,
        LoggerInterface $logger
    ) {
        $this->twilio = new Client($accountSid, $authToken);
        $this->verifyServiceSid = $verifyServiceSid;
        $this->logger = $logger;
    }

    /**
     * Send a verification code to the given phone number
     */
    public function sendVerificationCode(string $phoneNumber): array
    {
        try {
            // Format phone number to E.164 format
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $verification = $this->twilio->verify->v2->services($this->verifyServiceSid)
                ->verifications
                ->create($formattedPhone, 'sms');

            $this->logger->info('Verification code sent', [
                'phone' => $formattedPhone,
                'sid' => $verification->sid
            ]);

            return [
                'success' => true,
                'message' => 'Code de vérification envoyé avec succès',
                'sid' => $verification->sid
            ];
        } catch (TwilioException $e) {
            $this->logger->error('Failed to send verification code', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code de vérification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify the code provided by the user
     */
    public function verifyCode(string $phoneNumber, string $code): array
    {
        try {
            // Format phone number to E.164 format
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $verificationCheck = $this->twilio->verify->v2->services($this->verifyServiceSid)
                ->verificationChecks
                ->create($formattedPhone, ['code' => $code]);

            $isValid = $verificationCheck->status === 'approved';

            $this->logger->info('Code verification attempted', [
                'phone' => $formattedPhone,
                'valid' => $isValid,
                'status' => $verificationCheck->status
            ]);

            return [
                'success' => $isValid,
                'message' => $isValid 
                    ? 'Code vérifié avec succès' 
                    : 'Code invalide. Veuillez réessayer.',
                'status' => $verificationCheck->status
            ];
        } catch (TwilioException $e) {
            $this->logger->error('Failed to verify code', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification du code: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to E.164 format
     * Handles Tunisian numbers and international formats
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Handle Tunisian numbers
        if (strlen($phone) === 8 && in_array(substr($phone, 0, 2), ['20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '70', '71', '72', '73', '74', '75', '76', '77', '78', '79', '90', '91', '92', '93', '94', '95', '96', '97', '98', '99'])) {
            return '+216' . $phone;
        }

        // Handle numbers with country code but missing +
        if (strlen($phone) === 12 && substr($phone, 0, 3) === '216') {
            return '+' . $phone;
        }

        // Handle numbers already in E.164 format
        if (substr($phoneNumber, 0, 1) === '+') {
            return $phoneNumber;
        }

        // Default: assume it's a local number and add Tunisia country code
        if (strlen($phone) === 8) {
            return '+216' . $phone;
        }

        // If we can't determine the format, return as-is
        return $phoneNumber;
    }

    /**
     * Check if a phone number is valid for verification
     */
    public function isValidPhoneNumber(string $phoneNumber): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Check for Tunisian mobile numbers (8 digits starting with valid prefixes)
        if (strlen($phone) === 8) {
            $prefix = substr($phone, 0, 2);
            $validPrefixes = ['20', '21', '22', '23', '24', '25', '26', '27', '28', '29', 
                              '30', '31', '32', '33', '34', '35', '36', '37', '38', '39',
                              '40', '41', '42', '43', '44', '45', '46', '47', '48', '49',
                              '50', '51', '52', '53', '54', '55', '56', '57', '58', '59',
                              '70', '71', '72', '73', '74', '75', '76', '77', '78', '79',
                              '90', '91', '92', '93', '94', '95', '96', '97', '98', '99'];
            return in_array($prefix, $validPrefixes);
        }

        // Check for international format (+216 followed by 8 digits)
        if (strlen($phone) === 12 && substr($phone, 0, 3) === '216') {
            return true;
        }

        return false;
    }
}

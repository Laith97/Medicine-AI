<?php

namespace App\Services;

use Exception;
use Twilio\Rest\Client;

class TwilioWhatsAppProvider implements WhatsAppProviderInterface
{
    private array $config;
    private ?Client $client = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validateConfig();
    }

    public function validateConfig(): bool
    {
        $required = ['account_sid', 'auth_token', 'from'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new Exception("Missing required config: {$key}");
            }
        }
        return true;
    }

    public function sendMessage(string $to, string $message): bool
    {
        try {
            if (!$this->client) {
                $this->client = new Client(
                    $this->config['account_sid'],
                    $this->config['auth_token']
                );
            }

            // Format the recipient number for WhatsApp
            $to = $this->formatWhatsAppNumber($to);
            $from = $this->config['from'];

            $this->client->messages->create(
                $to,
                [
                    'from' => $from,
                    'body' => $message
                ]
            );

            return true;
        } catch (Exception $e) {
            \Log::error('Twilio WhatsApp send failed: ' . $e->getMessage());
            return false;
        }
    }

    private function formatWhatsAppNumber(string $number): string
    {
        // Ensure the number starts with '+' and is in E.164 format
        $number = preg_replace('/[^0-9+]/', '', $number);

        // Validate that the number has minimum length (at least 10 digits)
        $digitsOnly = preg_replace('/[^0-9]/', '', $number);
        if (strlen($digitsOnly) < 10) {
            throw new Exception("Invalid phone number format: must contain at least 10 digits");
        }

        if (substr($number, 0, 1) !== '+') {
            $number = '+' . $number;
        }
        return 'whatsapp:' . $number;
    }
}
<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class WhatsAppBusinessProvider implements WhatsAppProviderInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validateConfig();
    }

    public function validateConfig(): bool
    {
        $required = ['access_token', 'phone_number_id'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new Exception("Missing required config: {$key}");
            }
        }
        return true;
    }

    public function sendMessage(string $to, string $message): array
    {
        try {
            $url = "https://graph.facebook.com/v18.0/{$this->config['phone_number_id']}/messages";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['access_token'],
                'Content-Type' => 'application/json',
            ])->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->formatNumber($to),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'message_id' => $responseData['messages'][0]['id'] ?? null,
                    'status' => 'sent',
                ];
            } else {
                \Log::error('WhatsApp Business API error: ' . $response->body());
                return [
                    'success' => false,
                    'error' => $response->body(),
                ];
            }
        } catch (Exception $e) {
            \Log::error('WhatsApp Business API send failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getName(): string
    {
        return 'WhatsApp Business API';
    }

    public function getKey(): string
    {
        return 'graph_api';
    }

    private function formatNumber(string $number): string
    {
        // Remove any non-digit characters and ensure it's in the right format
        $number = preg_replace('/[^0-9+]/', '', $number);

        // If number starts with '+' remove it temporarily to process digits
        $hasPlus = substr($number, 0, 1) === '+';
        if ($hasPlus) {
            $number = substr($number, 1);
        }

        // Validate that the number has minimum length (at least 10 digits)
        if (strlen($number) < 10) {
            throw new Exception("Invalid phone number format: must contain at least 10 digits");
        }

        // Return the clean number (no leading country code modification)
        return $number;
    }
}
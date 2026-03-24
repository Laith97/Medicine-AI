<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaqnyatProvider implements SmsProviderInterface
{
    protected $bearerToken;
    protected $senderName;

    public function __construct(?array $config = null)
    {
        if ($config) {
            $this->bearerToken = $config['bearer_token'] ?? $config['api_key'] ?? config('sms.providers.taqnyat.bearer_token');
            $this->senderName = $config['sender_name'] ?? $config['senderName'] ?? config('sms.providers.taqnyat.sender_name');
        } else {
            $this->bearerToken = config('sms.providers.taqnyat.bearer_token');
            $this->senderName = config('sms.providers.taqnyat.sender_name');
        }
    }

    public function send(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Taqnyat configuration is incomplete',
                    'data' => []
                ];
            }

            // Format phone number for Saudi Arabia
            $to = $this->formatPhoneNumber($to);

            $response = Http::timeout(10)
                ->asJson()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                    'Content-Type' => 'application/json'
                ])
                ->post('https://api.taqnyat.sa/api/v1/send', [
                    'recipient' => $to,
                    'sender' => $this->senderName,
                    'body' => $message
                ]);

            $data = [];
            try {
                $data = $response->json();
            } catch (\Exception $e) {
                Log::warning('Taqnyat invalid JSON response', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500)
                ]);
            }

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                Log::info('SMS sent successfully via Taqnyat', [
                    'to' => $to,
                    'message_id' => $data['message_id'] ?? null
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via Taqnyat',
                    'data' => $data
                ];
            }

            Log::error('Taqnyat SMS failed', [
                'to' => $to,
                'status' => $response->status(),
                'response' => $data
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to send SMS via Taqnyat',
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('Taqnyat SMS exception', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Taqnyat SMS service error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Format phone number for Saudi Arabia
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle Saudi Arabian numbers
        if (str_starts_with($phone, '0')) {
            // Remove leading 0
            $phone = substr($phone, 1);
        }

        if (!str_starts_with($phone, '966')) {
            // Add Saudi country code if not present
            $phone = '966' . $phone;
        }

        return $phone;
    }

    public function getName(): string
    {
        return 'Taqnyat';
    }

    public function isConfigured(): bool
    {
        return !empty($this->bearerToken) && !empty($this->senderName);
    }

    public function getConfigRequirements(): array
    {
        return [
            'bearer_token' => 'Taqnyat Bearer Token (API Key)',
            'sender_name' => 'Sender Name'
        ];
    }

    public function getKey(): string
    {
        return 'taqnyat';
    }
}

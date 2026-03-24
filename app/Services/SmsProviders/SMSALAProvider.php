<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSALAProvider implements SmsProviderInterface
{
    protected $apiKey;
    protected $senderId;

    public function __construct(?array $config = null)
    {
        if ($config) {
            $this->apiKey = $config['api_key'] ?? $config['apiKey'] ?? config('sms.providers.smsala.api_key');
            $this->senderId = $config['sender_id'] ?? $config['senderId'] ?? config('sms.providers.smsala.sender_id');
        } else {
            $this->apiKey = config('sms.providers.smsala.api_key');
            $this->senderId = config('sms.providers.smsala.sender_id');
        }
    }

    public function send(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'SMSALA configuration is incomplete',
                    'data' => []
                ];
            }

            // Format phone number
            $to = $this->formatPhoneNumber($to);

            $response = Http::timeout(10)
                ->asForm()
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ])
                ->post('https://smsala.com/api/send', [
                    'key' => $this->apiKey,
                    'sender' => $this->senderId,
                    'numbers' => $to,
                    'msg' => $message
                ]);

            $responseText = $response->body();
            $data = json_decode($responseText, true) ?? ['raw' => $responseText];

            if ($response->successful()) {
                // SMSALA returns "1" for success
                if (trim($responseText) === '1' || ($data['status'] ?? '') === 'success') {
                    Log::info('SMS sent successfully via SMSALA', [
                        'to' => $to
                    ]);

                    return [
                        'success' => true,
                        'message' => 'SMS sent successfully via SMSALA',
                        'data' => $data
                    ];
                }
            }

            Log::error('SMSALA SMS failed', [
                'to' => $to,
                'status' => $response->status(),
                'response' => $responseText
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS via SMSALA: ' . ($data['error'] ?? $data['message'] ?? 'Unknown error'),
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('SMSALA SMS exception', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'SMSALA SMS service error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Format phone number
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
        return 'SMSALA';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->senderId);
    }

    public function getConfigRequirements(): array
    {
        return [
            'api_key' => 'SMSALA API Key',
            'sender_id' => 'Sender ID'
        ];
    }

    public function getKey(): string
    {
        return 'smsala';
    }
}

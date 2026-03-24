<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConnectSaudiProvider implements SmsProviderInterface
{
    protected $accountId;
    protected $apiKey;
    protected $senderName;

    public function __construct(?array $config = null)
    {
        if ($config) {
            $this->accountId = $config['account_id'] ?? $config['accountId'] ?? config('sms.providers.connectsaudi.account_id');
            $this->apiKey = $config['api_key'] ?? $config['apiKey'] ?? config('sms.providers.connectsaudi.api_key');
            $this->senderName = $config['sender_name'] ?? $config['senderName'] ?? config('sms.providers.connectsaudi.sender_name');
        } else {
            $this->accountId = config('sms.providers.connectsaudi.account_id');
            $this->apiKey = config('sms.providers.connectsaudi.api_key');
            $this->senderName = config('sms.providers.connectsaudi.sender_name');
        }
    }

    public function send(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'ConnectSaudi configuration is incomplete',
                    'data' => []
                ];
            }

            // Format phone number for Saudi Arabia
            $to = $this->formatPhoneNumber($to);

            $response = Http::timeout(10)
                ->asJson()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post('https://api.connectsaudi.com/sms/send', [
                    'account_id' => $this->accountId,
                    'to' => $to,
                    'from' => $this->senderName,
                    'message' => $message
                ]);

            $data = [];
            try {
                $data = $response->json();
            } catch (\Exception $e) {
                Log::warning('ConnectSaudi invalid JSON response', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500)
                ]);
            }

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                Log::info('SMS sent successfully via ConnectSaudi', [
                    'to' => $to,
                    'message_id' => $data['message_id'] ?? null
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via ConnectSaudi',
                    'data' => $data
                ];
            }

            Log::error('ConnectSaudi SMS failed', [
                'to' => $to,
                'status' => $response->status(),
                'response' => $data
            ]);

            return [
                'success' => false,
                'message' => $data['error'] ?? $data['message'] ?? 'Failed to send SMS via ConnectSaudi',
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('ConnectSaudi SMS exception', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'ConnectSaudi SMS service error: ' . $e->getMessage(),
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
        return 'ConnectSaudi';
    }

    public function isConfigured(): bool
    {
        return !empty($this->accountId) &&
               !empty($this->apiKey) &&
               !empty($this->senderName);
    }

    public function getConfigRequirements(): array
    {
        return [
            'account_id' => 'ConnectSaudi Account ID',
            'api_key' => 'ConnectSaudi API Key',
            'sender_name' => 'Sender Name'
        ];
    }

    public function getKey(): string
    {
        return 'connectsaudi';
    }
}

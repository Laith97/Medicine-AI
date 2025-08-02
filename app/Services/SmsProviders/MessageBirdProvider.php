<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageBirdProvider implements SmsProviderInterface
{
    protected $accessKey;
    protected $fromNumber;

    public function __construct()
    {
        $this->accessKey = config('sms.providers.messagebird.access_key');
        $this->fromNumber = config('sms.providers.messagebird.from_number');
    }

    public function send(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'MessageBird configuration is incomplete',
                    'data' => []
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'AccessKey ' . $this->accessKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->asForm()->post('https://rest.messagebird.com/messages', [
                'originator' => $this->fromNumber,
                'recipients' => $to,
                'body' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('SMS sent successfully via MessageBird', [
                    'to' => $to,
                    'id' => $data['id'] ?? null
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via MessageBird',
                    'data' => $data
                ];
            }

            $errorData = $response->json();
            Log::error('MessageBird SMS failed', [
                'to' => $to,
                'status' => $response->status(),
                'error' => $errorData
            ]);

            $errorMessage = 'Failed to send SMS via MessageBird';
            if (isset($errorData['errors']) && is_array($errorData['errors'])) {
                $errorMessage = $errorData['errors'][0]['description'] ?? $errorMessage;
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => $errorData
            ];

        } catch (\Exception $e) {
            Log::error('MessageBird SMS exception', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'MessageBird SMS service error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function getName(): string
    {
        return 'MessageBird';
    }

    public function isConfigured(): bool
    {
        return !empty($this->accessKey) && !empty($this->fromNumber);
    }

    public function getConfigRequirements(): array
    {
        return [
            'MESSAGEBIRD_ACCESS_KEY' => 'MessageBird Access Key',
            'MESSAGEBIRD_FROM_NUMBER' => 'MessageBird Originator (Phone Number or Text)'
        ];
    }

    public function getKey(): string
    {
        return 'messagebird';
    }
}

<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MsegatProvider implements SmsProviderInterface
{
    protected $email;
    protected $password;
    protected $senderName;

    public function __construct(?array $config = null)
    {
        if ($config) {
            $this->email = $config['email'] ?? $config['user_email'] ?? config('sms.providers.msegat.email');
            $this->password = $config['password'] ?? config('sms.providers.msegat.password');
            $this->senderName = $config['sender_name'] ?? $config['senderName'] ?? config('sms.providers.msegat.sender_name');
        } else {
            $this->email = config('sms.providers.msegat.email');
            $this->password = config('sms.providers.msegat.password');
            $this->senderName = config('sms.providers.msegat.sender_name');
        }
    }

    public function send(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Msegat configuration is incomplete',
                    'data' => []
                ];
            }

            // Format phone number for Saudi Arabia
            $to = $this->formatPhoneNumber($to);

            $response = Http::timeout(10)
                ->asJson()
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->post('https://www.msegat.com/api/sms/send', [
                    'userEmail' => $this->email,
                    'password' => $this->password,
                    'numbers' => $to,
                    'senderName' => $this->senderName,
                    'msg' => $message
                ]);

            $data = [];
            try {
                $data = $response->json();
            } catch (\Exception $e) {
                Log::warning('Msegat invalid JSON response', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500)
                ]);
            }

            if ($response->successful() && ($data['resultCode'] ?? '') === '1') {
                Log::info('SMS sent successfully via Msegat', [
                    'to' => $to,
                    'message_id' => $data['messageId'] ?? null
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via Msegat',
                    'data' => $data
                ];
            }

            Log::error('Msegat SMS failed', [
                'to' => $to,
                'status' => $response->status(),
                'response' => $data
            ]);

            return [
                'success' => false,
                'message' => $data['err'] ?? $data['description'] ?? 'Failed to send SMS via Msegat',
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('Msegat SMS exception', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Msegat SMS service error: ' . $e->getMessage(),
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
        return 'Msegat';
    }

    public function isConfigured(): bool
    {
        return !empty($this->email) &&
               !empty($this->password) &&
               !empty($this->senderName);
    }

    public function getConfigRequirements(): array
    {
        return [
            'email' => 'Msegat Email',
            'password' => 'Msegat Password',
            'sender_name' => 'Sender Name'
        ];
    }

    public function getKey(): string
    {
        return 'msegat';
    }
}

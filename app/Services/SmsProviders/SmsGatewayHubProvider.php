<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsGatewayHubProvider implements SmsProviderInterface
{
    private ?string $email;
    private ?string $password;
    private ?string $device;
    private string $baseUrl = 'https://smsgateway.me/api/v4';

    public function __construct(?array $config = null)
    {
        if ($config) {
            $this->email = $config['email'] ?? config('sms.providers.smsgatewayhub.email');
            $this->password = $config['password'] ?? config('sms.providers.smsgatewayhub.password');
            $this->device = $config['device'] ?? config('sms.providers.smsgatewayhub.device');
        } else {
            $this->email = config('sms.providers.smsgatewayhub.email');
            $this->password = config('sms.providers.smsgatewayhub.password');
            $this->device = config('sms.providers.smsgatewayhub.device');
        }
    }

    public function send(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'SMS Gateway Hub provider not configured. Please set SMSGATEWAYHUB_EMAIL, SMSGATEWAYHUB_PASSWORD, and SMSGATEWAYHUB_DEVICE in your environment.',
                    'data' => []
                ];
            }

            // Clean phone number (remove + and any non-numeric characters except +)
            $cleanTo = preg_replace('/[^\d+]/', '', $to);
            if (!str_starts_with($cleanTo, '+')) {
                $cleanTo = '+' . $cleanTo;
            }

            $response = Http::post($this->baseUrl . '/message/send', [
                'email' => $this->email,
                'password' => $this->password,
                'device' => $this->device,
                'number' => $cleanTo,
                'message' => $message,
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                Log::info('SMS Gateway Hub SMS sent successfully', [
                    'to' => $to,
                    'message_id' => $responseData['result']['id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via SMS Gateway Hub',
                    'data' => [
                        'provider' => 'smsgatewayhub',
                        'message_id' => $responseData['result']['id'] ?? null,
                    ]
                ];
            } else {
                $errorMessage = $responseData['error']['message'] ?? 'Unknown error';

                Log::error('SMS Gateway Hub SMS failed', [
                    'to' => $to,
                    'error' => $errorMessage,
                    'response' => $responseData
                ]);

                return [
                    'success' => false,
                    'message' => 'SMS Gateway Hub SMS failed: ' . $errorMessage,
                    'data' => ['provider' => 'smsgatewayhub', 'error' => $errorMessage]
                ];
            }

        } catch (\Exception $e) {
            Log::error('SMS Gateway Hub SMS exception', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'SMS Gateway Hub service error: ' . $e->getMessage(),
                'data' => ['provider' => 'smsgatewayhub']
            ];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->email) && !empty($this->password) && !empty($this->device);
    }

    public function getName(): string
    {
        return 'SMS Gateway Hub';
    }

    public function getConfigRequirements(): array
    {
        return [
            'SMSGATEWAYHUB_EMAIL' => 'Your SMS Gateway Hub account email',
            'SMSGATEWAYHUB_PASSWORD' => 'Your SMS Gateway Hub account password',
            'SMSGATEWAYHUB_DEVICE' => 'Your device ID from SMS Gateway Hub dashboard',
        ];
    }

    public function getKey(): string
    {
        return 'smsgatewayhub';
    }

    public function getDevices(): ?array
    {
        try {
            if (empty($this->email) || empty($this->password)) {
                return null;
            }

            $response = Http::get($this->baseUrl . '/device', [
                'email' => $this->email,
                'password' => $this->password,
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                return $responseData['result'] ?? [];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get SMS Gateway Hub devices', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getMessages(int $limit = 100): ?array
    {
        try {
            if (!$this->isConfigured()) {
                return null;
            }

            $response = Http::get($this->baseUrl . '/message', [
                'email' => $this->email,
                'password' => $this->password,
                'device' => $this->device,
                'limit' => $limit,
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                return $responseData['result'] ?? [];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get SMS Gateway Hub messages', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

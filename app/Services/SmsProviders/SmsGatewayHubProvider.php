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

    public function __construct()
    {
        $this->email = config('sms.providers.smsgatewayhub.email');
        $this->password = config('sms.providers.smsgatewayhub.password');
        $this->device = config('sms.providers.smsgatewayhub.device');
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

    /**
     * Get message status by ID
     *
     * @param string $messageId Message ID
     * @return array Response array with status information
     */
    public function getMessageStatus(string $messageId): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'SMS Gateway Hub provider not configured',
                    'data' => []
                ];
            }

            $response = Http::get($this->baseUrl . '/message/' . $messageId, [
                'email' => $this->email,
                'password' => $this->password,
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                return [
                    'success' => true,
                    'message' => 'Message status retrieved successfully',
                    'data' => $responseData['result'] ?? []
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to retrieve message status',
                'data' => []
            ];

        } catch (\Exception $e) {
            Log::error('SMS Gateway Hub message status check failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve message status: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Send bulk SMS messages
     *
     * @param array $recipients Array of recipient phone numbers
     * @param string $message Message content
     * @return array Response array with success status and results
     */
    public function sendBulkSms(array $recipients, string $message): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $recipient) {
            $result = $this->send($recipient, $message);
            $results[] = [
                'recipient' => $recipient,
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data']
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'success' => $failureCount === 0,
            'message' => "Bulk SMS sent: {$successCount} successful, {$failureCount} failed",
            'data' => [
                'results' => $results,
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]
        ];
    }

    /**
     * Get delivery report for a message
     *
     * @param string $messageId Message ID
     * @return array Delivery report data
     */
    public function getDeliveryReport(string $messageId): array
    {
        return $this->getMessageStatus($messageId);
    }

    /**
     * Get available devices
     *
     * @return array|null List of devices or null on failure
     */
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

    /**
     * Get messages
     *
     * @param int $limit Number of messages to retrieve
     * @return array|null List of messages or null on failure
     */
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

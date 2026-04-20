<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnifonicProvider implements SmsProviderInterface
{
    private ?string $appSid;
    private ?string $senderId;
    private string $baseUrl = 'https://el.cloud.unifonic.com/rest';

    public function __construct()
    {
        $this->appSid = config('sms.providers.unifonic.app_sid');
        $this->senderId = config('sms.providers.unifonic.sender_id');
    }

    public function send(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Unifonic provider not configured. Please set UNIFONIC_APP_SID and UNIFONIC_SENDER_ID in your environment.',
                    'data' => []
                ];
            }

            // Clean phone number (remove + and any non-numeric characters except +)
            $cleanTo = preg_replace('/[^\d+]/', '', $to);
            if (!str_starts_with($cleanTo, '+')) {
                $cleanTo = '+' . $cleanTo;
            }

            $response = Http::post($this->baseUrl . '/Messages/Send', [
                'AppSid' => $this->appSid,
                'SenderID' => $this->senderId,
                'Recipient' => $cleanTo,
                'Body' => $message,
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                Log::info('Unifonic SMS sent successfully', [
                    'to' => $to,
                    'message_id' => $responseData['data']['MessageID'] ?? null,
                    'cost' => $responseData['data']['Cost'] ?? null,
                    'balance' => $responseData['data']['Balance'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via Unifonic',
                    'data' => [
                        'provider' => 'unifonic',
                        'message_id' => $responseData['data']['MessageID'] ?? null,
                        'cost' => $responseData['data']['Cost'] ?? null,
                        'balance' => $responseData['data']['Balance'] ?? null,
                    ]
                ];
            } else {
                $errorMessage = $responseData['data']['ErrorCode'] ?? 'Unknown error';

                Log::error('Unifonic SMS failed', [
                    'to' => $to,
                    'error' => $errorMessage,
                    'response' => $responseData
                ]);

                return [
                    'success' => false,
                    'message' => 'Unifonic SMS failed: ' . $errorMessage,
                    'data' => ['provider' => 'unifonic', 'error' => $errorMessage]
                ];
            }

        } catch (\Exception $e) {
            Log::error('Unifonic SMS exception', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Unifonic SMS service error: ' . $e->getMessage(),
                'data' => ['provider' => 'unifonic']
            ];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->appSid) && !empty($this->senderId);
    }

    public function getName(): string
    {
        return 'Unifonic';
    }

    public function getConfigRequirements(): array
    {
        return [
            'UNIFONIC_APP_SID' => 'Your Unifonic App SID from dashboard',
            'UNIFONIC_SENDER_ID' => 'Your approved sender ID (e.g., your company name)',
        ];
    }

    public function getKey(): string
    {
        return 'unifonic';
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
                    'message' => 'Unifonic provider not configured',
                    'data' => []
                ];
            }

            $response = Http::get($this->baseUrl . '/Messages/GetStatus', [
                'AppSid' => $this->appSid,
                'MessageID' => $messageId,
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                return [
                    'success' => true,
                    'message' => 'Message status retrieved successfully',
                    'data' => $responseData['data'] ?? []
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to retrieve message status',
                'data' => []
            ];

        } catch (\Exception $e) {
            Log::error('Unifonic message status check failed', [
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
     * Get Unifonic account balance
     *
     * @return array|null Balance information or null on failure
     */
    public function getBalance(): ?array
    {
        try {
            if (!$this->isConfigured()) {
                return null;
            }

            $response = Http::get($this->baseUrl . '/Account/GetBalance', [
                'AppSid' => $this->appSid,
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                return [
                    'balance' => $responseData['data']['Balance'] ?? 0,
                    'currency' => $responseData['data']['CurrencyCode'] ?? 'USD',
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get Unifonic balance', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlivoProvider implements SmsProviderInterface
{
    protected $authId;
    protected $authToken;
    protected $fromNumber;

    public function __construct()
    {
        $this->authId = config('sms.providers.plivo.auth_id');
        $this->authToken = config('sms.providers.plivo.auth_token');
        $this->fromNumber = config('sms.providers.plivo.from_number');
    }

    public function send(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Plivo configuration is incomplete',
                    'data' => []
                ];
            }

            $response = Http::withBasicAuth($this->authId, $this->authToken)
                ->post("https://api.plivo.com/v1/Account/{$this->authId}/Message/", [
                    'src' => $this->fromNumber,
                    'dst' => $to,
                    'text' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('SMS sent successfully via Plivo', [
                    'to' => $to,
                    'message_uuid' => $data['message_uuid'][0] ?? null
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via Plivo',
                    'data' => $data
                ];
            }

            $errorData = $response->json();
            Log::error('Plivo SMS failed', [
                'to' => $to,
                'status' => $response->status(),
                'error' => $errorData
            ]);

            return [
                'success' => false,
                'message' => $errorData['error'] ?? 'Failed to send SMS via Plivo',
                'data' => $errorData
            ];

        } catch (\Exception $e) {
            Log::error('Plivo SMS exception', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Plivo SMS service error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function getName(): string
    {
        return 'Plivo';
    }

    public function isConfigured(): bool
    {
        return !empty($this->authId) &&
               !empty($this->authToken) &&
               !empty($this->fromNumber);
    }

    public function getConfigRequirements(): array
    {
        return [
            'PLIVO_AUTH_ID' => 'Plivo Auth ID',
            'PLIVO_AUTH_TOKEN' => 'Plivo Auth Token',
            'PLIVO_FROM_NUMBER' => 'Plivo Phone Number'
        ];
    }

    public function getKey(): string
    {
        return 'plivo';
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
                    'message' => 'Plivo configuration is incomplete',
                    'data' => []
                ];
            }

            $response = Http::withBasicAuth($this->authId, $this->authToken)
                ->get("https://api.plivo.com/v1/Account/{$this->authId}/Message/{$messageId}/");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Message status retrieved successfully',
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to retrieve message status',
                'data' => []
            ];

        } catch (\Exception $e) {
            Log::error('Plivo message status check failed', [
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
}

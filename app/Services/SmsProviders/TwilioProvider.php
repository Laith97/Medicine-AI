<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioProvider implements SmsProviderInterface
{
    protected $accountSid;
    protected $authToken;
    protected $fromNumber;

    public function __construct()
    {
        $this->accountSid = config('sms.providers.twilio.account_sid');
        $this->authToken = config('sms.providers.twilio.auth_token');
        $this->fromNumber = config('sms.providers.twilio.from_number');
    }

    public function send(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Twilio configuration is incomplete',
                    'data' => []
                ];
            }

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'From' => $this->fromNumber,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('SMS sent successfully via Twilio', [
                    'to' => $to,
                    'sid' => $data['sid'] ?? null
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via Twilio',
                    'data' => $data
                ];
            }

            $errorData = $response->json();
            Log::error('Twilio SMS failed', [
                'to' => $to,
                'status' => $response->status(),
                'error' => $errorData
            ]);

            return [
                'success' => false,
                'message' => $errorData['message'] ?? 'Failed to send SMS via Twilio',
                'data' => $errorData
            ];

        } catch (\Exception $e) {
            Log::error('Twilio SMS exception', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Twilio SMS service error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function getName(): string
    {
        return 'Twilio';
    }

    public function isConfigured(): bool
    {
        return !empty($this->accountSid) &&
               !empty($this->authToken) &&
               !empty($this->fromNumber);
    }

    public function getConfigRequirements(): array
    {
        return [
            'TWILIO_ACCOUNT_SID' => 'Twilio Account SID',
            'TWILIO_AUTH_TOKEN' => 'Twilio Auth Token',
            'TWILIO_FROM_NUMBER' => 'Twilio Phone Number'
        ];
    }

    public function getKey(): string
    {
        return 'twilio';
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
                    'message' => 'Twilio configuration is incomplete',
                    'data' => []
                ];
            }

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages/{$messageId}.json");

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
            Log::error('Twilio message status check failed', [
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

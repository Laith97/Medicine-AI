<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

class LogSmsProvider implements SmsProviderInterface
{
    /**
     * Send SMS message (logged only, no actual sending)
     */
    public function send(string $to, string $message): array
    {
        Log::info('SMS would be sent', [
            'to' => $to,
            'message' => $message,
            'provider' => 'log'
        ]);

        return [
            'success' => true,
            'message' => 'SMS logged successfully',
            'data' => ['logged_at' => now()->toISOString()]
        ];
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'Log Only';
    }

    /**
     * Check if provider is configured
     */
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Get provider configuration requirements
     */
    public function getConfigRequirements(): array
    {
        return [];
    }

    /**
     * Get provider unique key
     */
    public function getKey(): string
    {
        return 'log';
    }

    /**
     * Get message status by ID
     */
    public function getMessageStatus(string $messageId): array
    {
        return [
            'success' => true,
            'message' => 'Message logged',
            'data' => [
                'message_id' => $messageId,
                'status' => 'logged',
                'logged_at' => now()->toISOString()
            ]
        ];
    }

    /**
     * Send bulk SMS messages (logged only)
     */
    public function sendBulkSms(array $recipients, string $message): array
    {
        Log::info('Bulk SMS would be sent', [
            'recipients' => $recipients,
            'message' => $message,
            'provider' => 'log'
        ]);

        return [
            'success' => true,
            'message' => 'Bulk SMS logged successfully',
            'data' => [
                'logged_at' => now()->toISOString(),
                'recipient_count' => count($recipients)
            ]
        ];
    }

    /**
     * Get delivery report for a message
     */
    public function getDeliveryReport(string $messageId): array
    {
        return [
            'success' => true,
            'message' => 'Delivery report not available for logged messages',
            'data' => [
                'message_id' => $messageId,
                'status' => 'logged_no_delivery'
            ]
        ];
    }
}

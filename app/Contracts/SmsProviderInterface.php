<?php

namespace App\Contracts;

interface SmsProviderInterface
{
    /**
     * Send SMS message
     *
     * @param string $to
     * @param string $message
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function send(string $to, string $message): array;

    /**
     * Get provider name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if provider is configured
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Get provider configuration requirements
     *
     * @return array
     */
    public function getConfigRequirements(): array;

    /**
     * Get provider unique key
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Get message status by ID
     *
     * @param string $messageId Message ID
     * @return array Response array with status information
     */
    public function getMessageStatus(string $messageId): array;

    /**
     * Send bulk SMS messages
     *
     * @param array $recipients Array of recipient phone numbers
     * @param string $message Message content
     * @return array Response array with success status and results
     */
    public function sendBulkSms(array $recipients, string $message): array;

    /**
     * Get delivery report for a message
     *
     * @param string $messageId Message ID
     * @return array Delivery report data
     */
    public function getDeliveryReport(string $messageId): array;
}

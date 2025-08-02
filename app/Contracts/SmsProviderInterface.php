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
}

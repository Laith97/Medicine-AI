<?php

namespace App\Services;

use App\Models\UserWhatsAppConfiguration;
use App\Models\User;
use App\Models\NotificationPreference;
use Exception;

class WhatsAppNotificationService
{
    private array $providers = [];

    public function __construct()
    {
        $this->providers = [
            'twilio' => TwilioWhatsAppProvider::class,
            'graph_api' => WhatsAppBusinessProvider::class,
        ];
    }

    /**
     * Send a WhatsApp message using the appropriate provider and configuration
     */
    public function send(string $to, string $message, array $options = []): array
    {
        try {
            $providerKey = $options['provider_key'] ?? 'twilio';
            $providerConfig = $options['provider_config'] ?? [];

            if (!isset($this->providers[$providerKey])) {
                throw new Exception("Unsupported WhatsApp provider: {$providerKey}");
            }

            $providerClass = $this->providers[$providerKey];
            $provider = new $providerClass($providerConfig);

            return $provider->sendMessage($to, $message);
        } catch (Exception $e) {
            \Log::error('WhatsApp notification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available configurations for a user
     */
    public function getUserConfigurations(int $userId): array
    {
        return UserWhatsAppConfiguration::getActiveUserConfigurations($userId);
    }

    /**
     * Get available configurations for a hospital
     */
    public function getHospitalConfigurations(int $hospitalId): array
    {
        return UserWhatsAppConfiguration::getActiveHospitalConfigurations($hospitalId);
    }

    /**
     * Check if user has WhatsApp configured
     */
    public function isUserConfigured(int $userId): bool
    {
        $configs = $this->getUserConfigurations($userId);
        return !empty($configs);
    }

    /**
     * Check if hospital has WhatsApp configured
     */
    public function isHospitalConfigured(int $hospitalId): bool
    {
        $configs = $this->getHospitalConfigurations($hospitalId);
        return !empty($configs);
    }
}
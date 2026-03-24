<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Models\NotificationPreference;
use App\Models\UserWhatsAppConfiguration;
use App\Services\WhatsAppNotificationService;
use Illuminate\Notifications\Notification;
use Exception;

class WhatsAppChannel
{
    protected WhatsAppNotificationService $whatsappService;

    public function __construct(WhatsAppNotificationService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send the given notification via WhatsApp
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Check if the notifiable is a User
        if (!$notifiable instanceof User) {
            return;
        }

        // Get notification preferences for this user
        $preferences = $notifiable->getOrCreateNotificationPreferences();

        // Check if WhatsApp notifications are enabled for this user
        if (!$preferences->whatsapp_enabled) {
            return;
        }

        // Get the notification type to determine if it should be sent via WhatsApp
        $notificationData = $notification->toArray($notifiable);
        $notificationType = $notificationData['type'] ?? 'general';

        // Get the WhatsApp message from the notification first to make sure it exists
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);
        if (empty($message)) {
            return;
        }

        // Check if this specific notification type is enabled for WhatsApp
        $shouldSend = $this->shouldSendNotification($preferences, $notificationType);
        if (!$shouldSend) {
            return;
        }

        // Determine which WhatsApp configuration to use
        $config = $this->getUserConfiguration($notifiable);
        if (!$config) {
            return;
        }

        // Get the user's phone number for WhatsApp
        $phoneNumber = $notifiable->phone;
        if (empty($phoneNumber)) {
            \Log::warning("User {$notifiable->id} has no phone number for WhatsApp notifications");
            return;
        }

        // Send the WhatsApp message
        $options = [
            'provider_key' => $config->provider_key,
            'provider_config' => $config->provider_config,
        ];

        $result = $this->whatsappService->send($phoneNumber, $message, $options);

        if (!$result['success']) {
            \Log::warning("WhatsApp message failed for user {$notifiable->id}: " . ($result['error'] ?? 'Unknown error'));
        }
    }

    /**
     * Determine if the notification should be sent via WhatsApp based on user preferences
     */
    private function shouldSendNotification(NotificationPreference $preferences, string $notificationType): bool
    {
        switch ($notificationType) {
            case 'appointment_reminder':
                return $preferences->whatsapp_appointment_reminders;
            case 'appointment_booked':
            case 'diagnosis_submitted':
                return $preferences->whatsapp_diagnosis_updates;
            case 'review_submitted':
                return $preferences->whatsapp_review_requests;
            case 'system_alert':
                return $preferences->whatsapp_system_alerts;
            case 'urgent_alert':
                return $preferences->whatsapp_urgent_alerts;
            default:
                return false;
        }
    }

    /**
     * Get the appropriate WhatsApp configuration for the user
     */
    private function getUserConfiguration(User $user): ?UserWhatsAppConfiguration
    {
        // If user is a hospital admin, check hospital configurations
        if ($user->isHospitalAdmin() && $user->hospital_id) {
            $configs = UserWhatsAppConfiguration::where('hospital_id', $user->hospital_id)
                ->where('is_active', true)
                ->where('use_admin_config', false)
                ->get();

            if ($configs->count() > 0) {
                return $configs->first(); // Return first active configuration
            }

            // If no hospital-specific config, try to find a system-wide config
            $systemConfigs = UserWhatsAppConfiguration::whereNull('user_id')
                ->whereNull('hospital_id')
                ->where('is_active', true)
                ->get();

            if ($systemConfigs->count() > 0) {
                return $systemConfigs->first();
            }
        }

        // For doctors or other users, check their personal configurations
        if ($user->isDoctor()) {
            $configs = UserWhatsAppConfiguration::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('use_admin_config', false)
                ->get();

            if ($configs->count() > 0) {
                return $configs->first(); // Return first active configuration
            }
        }

        // Check for system-wide configurations
        $systemConfigs = UserWhatsAppConfiguration::whereNull('user_id')
            ->whereNull('hospital_id')
            ->where('is_active', true)
            ->get();

        return $systemConfigs->first();
    }
}
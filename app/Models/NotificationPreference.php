<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        // Email preferences
        'email_enabled',
        'email_appointment_reminders',
        'email_diagnosis_updates',
        'email_review_requests',
        'email_system_alerts',
        'email_marketing',
        // SMS preferences
        'sms_enabled',
        'sms_appointment_reminders',
        'sms_urgent_alerts',
        // WhatsApp preferences
        'whatsapp_enabled',
        'whatsapp_appointment_reminders',
        'whatsapp_urgent_alerts',
        'whatsapp_diagnosis_updates',
        'whatsapp_review_requests',
        'whatsapp_system_alerts',
        // In-app preferences
        'in_app_enabled',
        'in_app_sound',
        'in_app_desktop',
        'in_app_vibrate',
        // Frequency settings
        'frequency',
        'quiet_hours_start',
        'quiet_hours_end',
        'respect_quiet_hours',
        // Notification types
        'appointment_booked',
        'appointment_reminder',
        'diagnosis_submitted',
        'review_submitted',
        'voice_transcription_completed',
        'system_alert',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'email_appointment_reminders' => 'boolean',
        'email_diagnosis_updates' => 'boolean',
        'email_review_requests' => 'boolean',
        'email_system_alerts' => 'boolean',
        'email_marketing' => 'boolean',
        'sms_enabled' => 'boolean',
        'sms_appointment_reminders' => 'boolean',
        'sms_urgent_alerts' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'whatsapp_appointment_reminders' => 'boolean',
        'whatsapp_urgent_alerts' => 'boolean',
        'whatsapp_diagnosis_updates' => 'boolean',
        'whatsapp_review_requests' => 'boolean',
        'whatsapp_system_alerts' => 'boolean',
        'in_app_enabled' => 'boolean',
        'in_app_sound' => 'boolean',
        'in_app_desktop' => 'boolean',
        'in_app_vibrate' => 'boolean',
        'respect_quiet_hours' => 'boolean',
        'appointment_booked' => 'boolean',
        'appointment_reminder' => 'boolean',
        'diagnosis_submitted' => 'boolean',
        'review_submitted' => 'boolean',
        'voice_transcription_completed' => 'boolean',
        'system_alert' => 'boolean',
    ];

    /**
     * Get the user that owns the notification preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if all notifications are enabled
     */
    public function allNotificationsEnabled(): bool
    {
        return $this->appointment_booked &&
               $this->appointment_reminder &&
               $this->diagnosis_submitted &&
               $this->review_submitted &&
               $this->voice_transcription_completed &&
               $this->system_alert;
    }

    /**
     * Check if all notifications are disabled
     */
    public function allNotificationsDisabled(): bool
    {
        return !$this->appointment_booked &&
               !$this->appointment_reminder &&
               !$this->diagnosis_submitted &&
               !$this->review_submitted &&
               !$this->voice_transcription_completed &&
               !$this->system_alert;
    }

    /**
     * Check if all channels are enabled
     */
    public function allChannelsEnabled(): bool
    {
        return $this->email_enabled &&
               $this->sms_enabled &&
               $this->in_app_enabled;
    }

    /**
     * Check if all channels are disabled
     */
    public function allChannelsDisabled(): bool
    {
        return !$this->email_enabled &&
               !$this->sms_enabled &&
               !$this->in_app_enabled;
    }

    /**
     * Get active notification types
     */
    public function getActiveNotificationTypes(): array
    {
        $types = [];

        if ($this->appointment_booked) $types[] = 'appointment_booked';
        if ($this->appointment_reminder) $types[] = 'appointment_reminder';
        if ($this->diagnosis_submitted) $types[] = 'diagnosis_submitted';
        if ($this->review_submitted) $types[] = 'review_submitted';
        if ($this->voice_transcription_completed) $types[] = 'voice_transcription_completed';
        if ($this->system_alert) $types[] = 'system_alert';

        return $types;
    }

    /**
     * Get active channels
     */
    public function getActiveChannels(): array
    {
        $channels = [];

        if ($this->email_enabled) $channels[] = 'email';
        if ($this->sms_enabled) $channels[] = 'sms';
        if ($this->in_app_enabled) $channels[] = 'in_app';

        return $channels;
    }

    /**
     * Check if quiet hours are currently active
     */
    public function isQuietHoursActive(): bool
    {
        if (!$this->respect_quiet_hours) {
            return false;
        }

        $now = now();
        $currentTime = $now->format('H:i');
        $startTime = $this->quiet_hours_start;
        $endTime = $this->quiet_hours_end;

        // Handle overnight quiet hours (e.g., 22:00 to 08:00)
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Get notification frequency label
     */
    public function getFrequencyLabel(): string
    {
        return match($this->frequency) {
            'immediate' => 'Immediate',
            'hourly' => 'Hourly Digest',
            'daily' => 'Daily Digest',
            'weekly' => 'Weekly Digest',
            default => 'Immediate'
        };
    }

    /**
     * Get formatted quiet hours
     */
    public function getFormattedQuietHours(): string
    {
        return $this->quiet_hours_start . ' - ' . $this->quiet_hours_end;
    }

    /**
     * Enable all notifications
     */
    public function enableAllNotifications(): void
    {
        $this->update([
            'appointment_booked' => true,
            'appointment_reminder' => true,
            'diagnosis_submitted' => true,
            'review_submitted' => true,
            'voice_transcription_completed' => true,
            'system_alert' => true,
        ]);
    }

    /**
     * Disable all notifications
     */
    public function disableAllNotifications(): void
    {
        $this->update([
            'appointment_booked' => false,
            'appointment_reminder' => false,
            'diagnosis_submitted' => false,
            'review_submitted' => false,
            'voice_transcription_completed' => false,
            'system_alert' => false,
        ]);
    }

    /**
     * Enable all channels
     */
    public function enableAllChannels(): void
    {
        $this->update([
            'email_enabled' => true,
            'sms_enabled' => true,
            'in_app_enabled' => true,
        ]);
    }

    /**
     * Disable all channels
     */
    public function disableAllChannels(): void
    {
        $this->update([
            'email_enabled' => false,
            'sms_enabled' => false,
            'in_app_enabled' => false,
        ]);
    }

    /**
     * Reset to default settings
     */
    public function resetToDefaults(): void
    {
        $this->update([
            'email_enabled' => true,
            'email_appointment_reminders' => true,
            'email_diagnosis_updates' => true,
            'email_review_requests' => true,
            'email_system_alerts' => true,
            'email_marketing' => false,
            'sms_enabled' => false,
            'sms_appointment_reminders' => false,
            'sms_urgent_alerts' => true,
            'in_app_enabled' => true,
            'in_app_sound' => true,
            'in_app_desktop' => true,
            'in_app_vibrate' => false,
            'frequency' => 'immediate',
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'respect_quiet_hours' => true,
            'appointment_booked' => true,
            'appointment_reminder' => true,
            'diagnosis_submitted' => true,
            'review_submitted' => true,
            'voice_transcription_completed' => true,
            'system_alert' => true,
        ]);
    }

    /**
     * Check if user wants to receive WhatsApp notifications
     */
    public function wantsWhatsAppNotification(string $type): bool
    {
        if (!$this->whatsapp_enabled) {
            return false;
        }

        switch ($type) {
            case 'appointment_reminder':
                return $this->whatsapp_appointment_reminders;
            case 'urgent_alert':
            case 'system_alert':
                return $this->whatsapp_urgent_alerts;
            case 'diagnosis_submitted':
                return $this->whatsapp_diagnosis_updates;
            case 'review_submitted':
                return $this->whatsapp_review_requests;
            default:
                return false;
        }
    }

    /**
     * Enable all WhatsApp notifications
     */
    public function enableAllWhatsAppNotifications(): void
    {
        $this->update([
            'whatsapp_enabled' => true,
            'whatsapp_appointment_reminders' => true,
            'whatsapp_urgent_alerts' => true,
            'whatsapp_diagnosis_updates' => true,
            'whatsapp_review_requests' => true,
            'whatsapp_system_alerts' => true,
        ]);
    }

    /**
     * Disable all WhatsApp notifications
     */
    public function disableAllWhatsAppNotifications(): void
    {
        $this->update([
            'whatsapp_enabled' => false,
            'whatsapp_appointment_reminders' => false,
            'whatsapp_urgent_alerts' => false,
            'whatsapp_diagnosis_updates' => false,
            'whatsapp_review_requests' => false,
            'whatsapp_system_alerts' => false,
        ]);
    }

    /**
     * Get active WhatsApp notification types
     */
    public function getActiveWhatsAppNotificationTypes(): array
    {
        $types = [];

        if ($this->whatsapp_appointment_reminders) $types[] = 'appointment_reminder';
        if ($this->whatsapp_urgent_alerts) $types[] = 'urgent_alert';
        if ($this->whatsapp_diagnosis_updates) $types[] = 'diagnosis_submitted';
        if ($this->whatsapp_review_requests) $types[] = 'review_submitted';
        if ($this->whatsapp_system_alerts) $types[] = 'system_alert';

        return $types;
    }
}

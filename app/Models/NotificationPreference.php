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
        'appointment_status_changed',
        'appointment_confirmed',
        'appointment_cancelled',
        'appointment_completed',
        'appointment_no_show',
        'diagnosis_submitted',
        'review_submitted',
        'voice_transcription_completed',
        'system_alert',
        // Real-time preferences
        'realtime_appointment_updates',
        'realtime_critical_alerts',
        'push_appointment_status',
        'push_critical_updates',
        // Waitlist notification types
        'waitlist_slot_available',
        'waitlist_offer_expiring',
        'waitlist_position_update',
        'waitlist_auto_booked',
        'waitlist_expired',
        // Waitlist channel preferences
        'waitlist_channels',
        // Waitlist frequency controls
        'waitlist_frequency',
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
        'appointment_status_changed' => 'boolean',
        'appointment_confirmed' => 'boolean',
        'appointment_cancelled' => 'boolean',
        'appointment_completed' => 'boolean',
        'appointment_no_show' => 'boolean',
        'diagnosis_submitted' => 'boolean',
        'review_submitted' => 'boolean',
        'voice_transcription_completed' => 'boolean',
        'system_alert' => 'boolean',
        'realtime_appointment_updates' => 'boolean',
        'realtime_critical_alerts' => 'boolean',
        'push_appointment_status' => 'boolean',
        'push_critical_updates' => 'boolean',
        // Waitlist notification types
        'waitlist_slot_available' => 'boolean',
        'waitlist_offer_expiring' => 'boolean',
        'waitlist_position_update' => 'boolean',
        'waitlist_auto_booked' => 'boolean',
        'waitlist_expired' => 'boolean',
        // Waitlist channel preferences
        'waitlist_channels' => 'array',
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
                $this->appointment_status_changed &&
                $this->appointment_confirmed &&
                $this->appointment_cancelled &&
                $this->appointment_completed &&
                $this->appointment_no_show &&
                $this->diagnosis_submitted &&
                $this->review_submitted &&
                $this->voice_transcription_completed &&
                $this->system_alert &&
                $this->waitlist_slot_available &&
                $this->waitlist_offer_expiring &&
                $this->waitlist_position_update &&
                $this->waitlist_auto_booked &&
                $this->waitlist_expired;
    }

    /**
     * Check if all notifications are disabled
     */
    public function allNotificationsDisabled(): bool
    {
        return !$this->appointment_booked &&
                !$this->appointment_reminder &&
                !$this->appointment_status_changed &&
                !$this->appointment_confirmed &&
                !$this->appointment_cancelled &&
                !$this->appointment_completed &&
                !$this->appointment_no_show &&
                !$this->diagnosis_submitted &&
                !$this->review_submitted &&
                !$this->voice_transcription_completed &&
                !$this->system_alert &&
                !$this->waitlist_slot_available &&
                !$this->waitlist_offer_expiring &&
                !$this->waitlist_position_update &&
                !$this->waitlist_auto_booked &&
                !$this->waitlist_expired;
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
        if ($this->appointment_status_changed) $types[] = 'appointment_status_changed';
        if ($this->appointment_confirmed) $types[] = 'appointment_confirmed';
        if ($this->appointment_cancelled) $types[] = 'appointment_cancelled';
        if ($this->appointment_completed) $types[] = 'appointment_completed';
        if ($this->appointment_no_show) $types[] = 'appointment_no_show';
        if ($this->diagnosis_submitted) $types[] = 'diagnosis_submitted';
        if ($this->review_submitted) $types[] = 'review_submitted';
        if ($this->voice_transcription_completed) $types[] = 'voice_transcription_completed';
        if ($this->system_alert) $types[] = 'system_alert';

        // Waitlist notification types
        if ($this->waitlist_slot_available) $types[] = 'waitlist_slot_available';
        if ($this->waitlist_offer_expiring) $types[] = 'waitlist_offer_expiring';
        if ($this->waitlist_position_update) $types[] = 'waitlist_position_update';
        if ($this->waitlist_auto_booked) $types[] = 'waitlist_auto_booked';
        if ($this->waitlist_expired) $types[] = 'waitlist_expired';

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
     * Check if all waitlist notifications are enabled
     */
    public function allWaitlistNotificationsEnabled(): bool
    {
        return $this->waitlist_slot_available &&
               $this->waitlist_offer_expiring &&
               $this->waitlist_position_update &&
               $this->waitlist_auto_booked &&
               $this->waitlist_expired;
    }

    /**
     * Check if all waitlist notifications are disabled
     */
    public function allWaitlistNotificationsDisabled(): bool
    {
        return !$this->waitlist_slot_available &&
               !$this->waitlist_offer_expiring &&
               !$this->waitlist_position_update &&
               !$this->waitlist_auto_booked &&
               !$this->waitlist_expired;
    }

    /**
     * Get active waitlist notification types
     */
    public function getActiveWaitlistNotificationTypes(): array
    {
        $types = [];

        if ($this->waitlist_slot_available) $types[] = 'waitlist_slot_available';
        if ($this->waitlist_offer_expiring) $types[] = 'waitlist_offer_expiring';
        if ($this->waitlist_position_update) $types[] = 'waitlist_position_update';
        if ($this->waitlist_auto_booked) $types[] = 'waitlist_auto_booked';
        if ($this->waitlist_expired) $types[] = 'waitlist_expired';

        return $types;
    }

    /**
     * Get waitlist notification channels
     */
    public function getWaitlistChannels(): array
    {
        return $this->waitlist_channels ?? ['database'];
    }

    /**
     * Get waitlist frequency label
     */
    public function getWaitlistFrequencyLabel(): string
    {
        return match($this->waitlist_frequency) {
            'immediate' => 'Immediate',
            'hourly' => 'Hourly Digest',
            'daily' => 'Daily Digest',
            'weekly' => 'Weekly Digest',
            default => 'Immediate'
        };
    }

    /**
     * Enable all waitlist notifications
     */
    public function enableAllWaitlistNotifications(): void
    {
        $this->update([
            'waitlist_slot_available' => true,
            'waitlist_offer_expiring' => true,
            'waitlist_position_update' => true,
            'waitlist_auto_booked' => true,
            'waitlist_expired' => true,
        ]);
    }

    /**
     * Disable all waitlist notifications
     */
    public function disableAllWaitlistNotifications(): void
    {
        $this->update([
            'waitlist_slot_available' => false,
            'waitlist_offer_expiring' => false,
            'waitlist_position_update' => false,
            'waitlist_auto_booked' => false,
            'waitlist_expired' => false,
        ]);
    }

    /**
     * Enable all notifications
     */
    public function enableAllNotifications(): void
    {
        $this->update([
            'appointment_booked' => true,
            'appointment_reminder' => true,
            'appointment_status_changed' => true,
            'appointment_confirmed' => true,
            'appointment_cancelled' => true,
            'appointment_completed' => true,
            'appointment_no_show' => true,
            'diagnosis_submitted' => true,
            'review_submitted' => true,
            'voice_transcription_completed' => true,
            'system_alert' => true,
            // Waitlist notifications
            'waitlist_slot_available' => true,
            'waitlist_offer_expiring' => true,
            'waitlist_position_update' => true,
            'waitlist_auto_booked' => true,
            'waitlist_expired' => true,
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
            'appointment_status_changed' => false,
            'appointment_confirmed' => false,
            'appointment_cancelled' => false,
            'appointment_completed' => false,
            'appointment_no_show' => false,
            'diagnosis_submitted' => false,
            'review_submitted' => false,
            'voice_transcription_completed' => false,
            'system_alert' => false,
            // Waitlist notifications
            'waitlist_slot_available' => false,
            'waitlist_offer_expiring' => false,
            'waitlist_position_update' => false,
            'waitlist_auto_booked' => false,
            'waitlist_expired' => false,
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
            'appointment_status_changed' => true,
            'appointment_confirmed' => true,
            'appointment_cancelled' => true,
            'appointment_completed' => true,
            'appointment_no_show' => true,
            'diagnosis_submitted' => true,
            'review_submitted' => true,
            'voice_transcription_completed' => true,
            'system_alert' => true,
            'realtime_appointment_updates' => true,
            'realtime_critical_alerts' => true,
            'push_appointment_status' => true,
            'push_critical_updates' => true,
            // Waitlist notification defaults
            'waitlist_slot_available' => true,
            'waitlist_offer_expiring' => true,
            'waitlist_position_update' => true,
            'waitlist_auto_booked' => true,
            'waitlist_expired' => true,
            'waitlist_channels' => ['database', 'mail'],
            'waitlist_frequency' => 'immediate',
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

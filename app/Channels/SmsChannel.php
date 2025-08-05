<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\SmsService;

class SmsChannel
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $message = $notification->toSms($notifiable);

        if (empty($message)) {
            return;
        }

        // Get the phone number from the notifiable
        $phone = $notifiable->phone ?? $notifiable->routeNotificationFor('sms');

        if (empty($phone)) {
            return;
        }

        try {
            // Send SMS using the SMS service
            $this->smsService->sendSms($phone, $message);
        } catch (\Exception $e) {
            // Log SMS sending failure but don't break the notification process
            \Log::error('Failed to send SMS notification: ' . $e->getMessage(), [
                'phone' => $phone,
                'notification_class' => get_class($notification),
            ]);
        }
    }
}

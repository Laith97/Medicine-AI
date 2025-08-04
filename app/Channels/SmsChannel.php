<?php

namespace App\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!$notifiable->phone) {
            Log::info('SMS notification skipped - no phone number', [
                'user_id' => $notifiable->id,
                'notification' => get_class($notification)
            ]);
            return;
        }

        if (!method_exists($notification, 'toSms')) {
            Log::warning('SMS notification skipped - toSms method not found', [
                'user_id' => $notifiable->id,
                'notification' => get_class($notification)
            ]);
            return;
        }

        try {
            $result = $notification->toSms($notifiable);
            
            Log::info('SMS notification processed', [
                'user_id' => $notifiable->id,
                'phone' => $notifiable->phone,
                'notification' => get_class($notification),
                'success' => $result['success'] ?? false
            ]);
            
        } catch (\Exception $e) {
            Log::error('SMS notification failed', [
                'user_id' => $notifiable->id,
                'phone' => $notifiable->phone,
                'notification' => get_class($notification),
                'error' => $e->getMessage()
            ]);
        }
    }
}
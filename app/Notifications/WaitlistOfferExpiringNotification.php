<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\WaitlistEntry;
use App\Services\NotificationCompressionService;

class WaitlistOfferExpiringNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $waitlistEntry;

    /**
     * Create a new notification instance.
     */
    public function __construct(WaitlistEntry $waitlistEntry)
    {
        $this->waitlistEntry = $waitlistEntry;

        // Use realtime queue for instant processing
        $this->onQueue('realtime');

        // Ensure notification is broadcast immediately
        $this->delay(0);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail', 'sms'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $doctorName = $this->waitlistEntry?->waitlist?->doctor?->user?->name ?? 'Unknown Doctor';
        $expiresAt = $this->waitlistEntry?->expires_at;

        return [
            'type' => 'waitlist_offer_expiring',
            'title' => 'Appointment Offer Expiring Soon',
            'message' => "Your appointment offer with Dr. {$doctorName} expires in 1 hour",
            'icon' => 'clock',
            'link' => $this->waitlistEntry?->id ? route('patient.waitlist.status', ['waitlist' => $this->waitlistEntry->id]) : '#',
            'link_text' => 'Book Now',
            'related_type' => 'waitlist_entry',
            'related_id' => $this->waitlistEntry?->id,
            'data' => [
                'waitlist_entry_id' => $this->waitlistEntry?->id,
                'doctor_name' => $doctorName,
                'position' => $this->waitlistEntry?->position,
                'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
                'time_remaining' => $expiresAt ? now()->diffInMinutes($expiresAt) : null,
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $doctorName = $this->waitlistEntry?->waitlist?->doctor?->user?->name ?? 'Unknown Doctor';
        $expiresAt = $this->waitlistEntry?->expires_at;

        return (new MailMessage)
            ->subject('Appointment Offer Expiring Soon')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your appointment offer with Dr. {$doctorName} is expiring soon!")
            ->line('Your position in the waitlist: #' . ($this->waitlistEntry?->position ?? 'N/A'))
            ->when($expiresAt, function ($mail) use ($expiresAt) {
                return $mail->line('Offer expires on: ' . $expiresAt->format('M j, Y g:i A'));
            })
            ->action('Book Now', $this->waitlistEntry?->id ? route('patient.waitlist.status', ['waitlist' => $this->waitlistEntry->id]) : '#')
            ->line('Don\'t let this opportunity slip away!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $doctorName = $this->waitlistEntry?->waitlist?->doctor?->user?->name ?? 'Unknown Doctor';
        $doctorId = $this->waitlistEntry?->waitlist?->doctor?->id ?? 0;
        $hospitalId = $this->waitlistEntry?->waitlist?->doctor?->hospital_id ?? 0;
        $expiresAt = $this->waitlistEntry?->expires_at;

        $expiresText = $expiresAt ? 'Expires: ' . $expiresAt->format('M j, g:i A') : '';

        return [
            'message' => "URGENT: Your appointment offer with Dr. {$doctorName} expires soon! {$expiresText} Book now: " . ($this->waitlistEntry?->id ? route('patient.waitlist.status', ['waitlist' => $this->waitlistEntry->id]) : '#'),
            'options' => [
                'doctor_id' => $doctorId,
                'hospital_id' => $hospitalId,
                'context' => 'waitlist_slot',
                'context_id' => $this->waitlistEntry?->id,
            ]
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $doctorName = $this->waitlistEntry?->waitlist?->doctor?->user?->name ?? 'Unknown Doctor';
        $doctorId = $this->waitlistEntry?->waitlist?->doctor?->id ?? 0;
        $expiresAt = $this->waitlistEntry?->expires_at;

        $payload = [
            'id' => $this->id,
            'type' => 'waitlist_offer_expiring',
            'title' => 'Appointment Offer Expiring Soon',
            'message' => "Your appointment offer with Dr. {$doctorName} expires in 1 hour",
            'body' => "Your appointment offer with Dr. {$doctorName} expires in 1 hour",
            'icon' => 'clock',
            'link' => $this->waitlistEntry?->id ? route('patient.waitlist.status', ['waitlist' => $this->waitlistEntry->id]) : '#',
            'link_text' => 'Book Now',
            'data' => [
                'waitlist_entry_id' => $this->waitlistEntry?->id,
                'doctor_name' => $doctorName,
                'doctor_id' => $doctorId,
                'position' => $this->waitlistEntry?->position,
                'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
                'time_remaining' => $expiresAt ? now()->diffInMinutes($expiresAt) : null,
            ],
            'created_at' => now()->toISOString()
        ];

        // Compress payload if beneficial
        $compressionService = app(NotificationCompressionService::class);
        $compressedPayload = $compressionService->compressPayload($payload);

        return new BroadcastMessage($compressedPayload);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        $userId = $this->waitlistEntry?->user_id;
        return [
            new PrivateChannel('App.User.' . $userId)
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'waitlist-offer-expiring';
    }
}

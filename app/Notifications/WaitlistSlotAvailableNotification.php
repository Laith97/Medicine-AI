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

class WaitlistSlotAvailableNotification extends Notification implements ShouldBroadcast
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
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $doctorName = $this->waitlistEntry->waitlist->doctor->user->name ?? 'Unknown Doctor';
        $doctorId = $this->waitlistEntry->waitlist->doctor->id ?? 0;
        $hospitalId = $this->waitlistEntry->waitlist->doctor->hospital_id ?? 0;

        return [
            'message' => "Slot available! Book now for your appointment with Dr. {$doctorName}. Position: #{$this->waitlistEntry->position}. Book: " . route('waitlist.show', $this->waitlistEntry->id),
            'options' => [
                'doctor_id' => $doctorId,
                'hospital_id' => $hospitalId,
                'context' => 'waitlist_slot',
                'context_id' => $this->waitlistEntry->id,
            ]
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $doctorName = $this->waitlistEntry->waitlist->doctor->user->name ?? 'Unknown Doctor';

        return [
            'type' => 'waitlist_slot_available',
            'title' => 'Appointment Slot Available',
            'message' => "A slot has become available for your waitlisted appointment with Dr. {$doctorName}",
            'icon' => 'calendar-check',
            'link' => route('waitlist.show', $this->waitlistEntry->id),
            'link_text' => 'Book Now',
            'related_type' => 'waitlist_entry',
            'related_id' => $this->waitlistEntry->id,
            'data' => [
                'waitlist_entry_id' => $this->waitlistEntry->id,
                'doctor_name' => $doctorName,
                'position' => $this->waitlistEntry->position,
                'expires_at' => $this->waitlistEntry->expires_at?->format('Y-m-d H:i:s'),
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $doctorName = $this->waitlistEntry->waitlist->doctor->user->name ?? 'Unknown Doctor';

        return (new MailMessage)
            ->subject('Appointment Slot Available')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Great news! A slot has become available for your waitlisted appointment with Dr. {$doctorName}.")
            ->line('Your position in the waitlist: #' . $this->waitlistEntry->position)
            ->when($this->waitlistEntry->expires_at, function ($mail) {
                return $mail->line('This offer expires on: ' . $this->waitlistEntry->expires_at->format('M j, Y g:i A'));
            })
            ->action('Book Now', route('waitlist.show', $this->waitlistEntry->id))
            ->line('Don\'t miss this opportunity to secure your appointment!');
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $doctorName = $this->waitlistEntry->waitlist->doctor->user->name ?? 'Unknown Doctor';
        $doctorId = $this->waitlistEntry->waitlist->doctor->id ?? 0;

        $payload = [
            'id' => $this->id,
            'type' => 'waitlist_slot_available',
            'title' => 'Appointment Slot Available',
            'message' => "A slot has become available for your waitlisted appointment with Dr. {$doctorName}",
            'body' => "A slot has become available for your waitlisted appointment with Dr. {$doctorName}",
            'icon' => 'calendar-check',
            'link' => route('waitlist.show', $this->waitlistEntry->id),
            'link_text' => 'Book Now',
            'data' => [
                'waitlist_entry_id' => $this->waitlistEntry->id,
                'doctor_name' => $doctorName,
                'doctor_id' => $doctorId,
                'position' => $this->waitlistEntry->position,
                'expires_at' => $this->waitlistEntry->expires_at?->format('Y-m-d H:i:s'),
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
        $userId = $this->waitlistEntry->user_id;
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
        return 'waitlist-slot-available';
    }
}

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

class WaitlistPositionUpdateNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $waitlistEntry;
    protected $oldPosition;
    protected $newPosition;

    /**
     * Create a new notification instance.
     */
    public function __construct(WaitlistEntry $waitlistEntry, int $oldPosition, int $newPosition)
    {
        $this->waitlistEntry = $waitlistEntry;
        $this->oldPosition = $oldPosition;
        $this->newPosition = $newPosition;

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
        return ['database', 'broadcast', 'sms'];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $doctorName = $this->waitlistEntry->waitlist->doctor->user->name ?? 'Unknown Doctor';
        $doctorId = $this->waitlistEntry->waitlist->doctor->id ?? 0;
        $hospitalId = $this->waitlistEntry->waitlist->doctor->hospital_id ?? 0;
        $improvement = $this->oldPosition - $this->newPosition;

        return [
            'message' => "Your waitlist position with Dr. {$doctorName} improved from #{$this->oldPosition} to #{$this->newPosition} (+{$improvement}). View: " . route('waitlist.show', $this->waitlistEntry->id),
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
        $improvement = $this->oldPosition - $this->newPosition;

        return [
            'type' => 'waitlist_position_update',
            'title' => 'Waitlist Position Updated',
            'message' => "Your position with Dr. {$doctorName} has improved from #{$this->oldPosition} to #{$this->newPosition}",
            'icon' => 'list-ol',
            'link' => route('waitlist.show', $this->waitlistEntry->id),
            'link_text' => 'View Details',
            'related_type' => 'waitlist_entry',
            'related_id' => $this->waitlistEntry->id,
            'data' => [
                'waitlist_entry_id' => $this->waitlistEntry->id,
                'doctor_name' => $doctorName,
                'old_position' => $this->oldPosition,
                'new_position' => $this->newPosition,
                'improvement' => $improvement,
            ]
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $doctorName = $this->waitlistEntry->waitlist->doctor->user->name ?? 'Unknown Doctor';
        $doctorId = $this->waitlistEntry->waitlist->doctor->id ?? 0;
        $improvement = $this->oldPosition - $this->newPosition;

        $payload = [
            'id' => $this->id,
            'type' => 'waitlist_position_update',
            'title' => 'Waitlist Position Updated',
            'message' => "Your position with Dr. {$doctorName} has improved from #{$this->oldPosition} to #{$this->newPosition}",
            'body' => "Your position with Dr. {$doctorName} has improved from #{$this->oldPosition} to #{$this->newPosition}",
            'icon' => 'list-ol',
            'link' => route('waitlist.show', $this->waitlistEntry->id),
            'link_text' => 'View Details',
            'data' => [
                'waitlist_entry_id' => $this->waitlistEntry->id,
                'doctor_name' => $doctorName,
                'doctor_id' => $doctorId,
                'old_position' => $this->oldPosition,
                'new_position' => $this->newPosition,
                'improvement' => $improvement,
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
        return 'waitlist-position-update';
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\WaitlistMatchOffer;
use App\Services\NotificationCompressionService;

class WaitlistOfferNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $offer;

    /**
     * Create a new notification instance.
     */
    public function __construct(WaitlistMatchOffer $offer)
    {
        $this->offer = $offer;
        $this->onQueue('realtime');
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
        $doctorName = $this->offer->doctor->user->name ?? 'Unknown Doctor';
        $slotDate = $this->offer->availabilitySlot->date->format('M j');
        $slotTime = substr($this->offer->availabilitySlot->start_time, 0, 5);

        return [
            'message' => "You've been matched! Appointment available with Dr. {$doctorName} on {$slotDate} at {$slotTime}. Accept: " . route('waitlist.offer', $this->offer->id),
            'options' => [
                'doctor_id' => $this->offer->doctor_id,
                'hospital_id' => $this->offer->doctor->hospital_id ?? 0,
                'context' => 'waitlist_offer',
                'context_id' => $this->offer->id,
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
        $doctorName = $this->offer->doctor->user->name ?? 'Unknown Doctor';
        $slotDate = $this->offer->availabilitySlot->date->format('M j, Y');
        $slotTime = substr($this->offer->availabilitySlot->start_time, 0, 5);

        return [
            'type' => 'waitlist_offer',
            'title' => 'Appointment Offer Matched',
            'message' => "You've been matched with an appointment slot with Dr. {$doctorName} on {$slotDate} at {$slotTime}",
            'icon' => 'calendar-check',
            'link' => route('waitlist.offer', $this->offer->id),
            'link_text' => 'Accept Offer',
            'related_type' => 'waitlist_match_offer',
            'related_id' => $this->offer->id,
            'data' => [
                'offer_id' => $this->offer->id,
                'doctor_name' => $doctorName,
                'slot_date' => $slotDate,
                'slot_time' => $slotTime,
                'expires_at' => $this->offer->expires_at?->format('Y-m-d H:i:s'),
                'match_score' => $this->offer->match_score,
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $doctorName = $this->offer->doctor->user->name ?? 'Unknown Doctor';
        $slotDate = $this->offer->availabilitySlot->date->format('M j, Y');
        $slotTime = substr($this->offer->availabilitySlot->start_time, 0, 5);

        return (new MailMessage)
            ->subject('Appointment Offer - Action Required')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Great news! You've been matched with an appointment slot with Dr. {$doctorName}.")
            ->line("Date: {$slotDate}")
            ->line("Time: {$slotTime}")
            ->when($this->offer->expires_at, function ($mail) {
                return $mail->line('This offer expires on: ' . $this->offer->expires_at->format('M j, Y g:i A'));
            })
            ->action('Accept Offer', route('waitlist.offer', $this->offer->id))
            ->line('Don\'t miss this opportunity!');
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $doctorName = $this->offer->doctor->user->name ?? 'Unknown Doctor';
        $slotDate = $this->offer->availabilitySlot->date->format('M j, Y');
        $slotTime = substr($this->offer->availabilitySlot->start_time, 0, 5);

        $payload = [
            'id' => $this->id,
            'type' => 'waitlist_offer',
            'title' => 'Appointment Offer Matched',
            'message' => "You've been matched with Dr. {$doctorName} on {$slotDate} at {$slotTime}",
            'body' => "You've been matched with an appointment slot with Dr. {$doctorName}",
            'icon' => 'calendar-check',
            'link' => route('waitlist.offer', $this->offer->id),
            'link_text' => 'Accept Offer',
            'data' => [
                'offer_id' => $this->offer->id,
                'doctor_name' => $doctorName,
                'slot_date' => $slotDate,
                'slot_time' => $slotTime,
                'expires_at' => $this->offer->expires_at?->format('Y-m-d H:i:s'),
                'match_score' => $this->offer->match_score,
            ],
            'created_at' => now()->toISOString()
        ];

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
        return [
            new PrivateChannel('App.User.' . $this->offer->patient_id)
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'waitlist-offer';
    }
}

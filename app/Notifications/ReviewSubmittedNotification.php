<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\Review;

class ReviewSubmittedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $review;

    /**
     * Create a new notification instance.
     */
    public function __construct(Review $review)
    {
        $this->review = $review;

        // Use realtime queue for instant processing
        $this->onQueue('realtime');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $patientName = $this->review->is_anonymous ? 'Anonymous Patient' : ($this->review->patient ? $this->review->patient->name : $this->review->guest_name);

        return [
            'type' => 'review_submitted',
            'title' => 'New Review Submitted',
            'message' => "A new review has been submitted by {$patientName} with a rating of {$this->review->rating} stars.",
            'icon' => 'star',
            'link' => route('doctor.reviews.index'),
            'link_text' => 'View Reviews',
            'related_type' => 'review',
            'related_id' => $this->review->id,
            'data' => [
                'review_id' => $this->review->id,
                'patient_name' => $patientName,
                'rating' => $this->review->rating,
                'comment' => $this->review->comment,
                'is_anonymous' => $this->review->is_anonymous,
                'submitted_at' => $this->review->created_at->format('Y-m-d H:i:s'),
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Review Submitted')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("A new review has been submitted for {$this->review->doctor->name}")
            ->line('Rating: ' . $this->review->rating . ' stars')
            ->line('Comment: ' . $this->review->comment)
            ->action('View Review', route('reviews.show', $this->review))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "New review submitted for {$this->review->doctor->name}. Rating: {$this->review->rating} stars. View details: " . route('reviews.show', $this->review);
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $patientName = $this->review->is_anonymous ? 'Anonymous Patient' : ($this->review->patient ? $this->review->patient->name : $this->review->guest_name);

        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'review_submitted',
            'title' => 'New Review Submitted',
            'message' => "A new review has been submitted by {$patientName} with a rating of {$this->review->rating} stars.",
            'body' => "A new review has been submitted by {$patientName} with a rating of {$this->review->rating} stars.",
            'icon' => 'star',
            'link' => route('doctor.reviews.index'),
            'link_text' => 'View Reviews',
            'data' => [
                'review_id' => $this->review->id,
                'patient_name' => $patientName,
                'rating' => $this->review->rating,
                'comment' => $this->review->comment,
                'is_anonymous' => $this->review->is_anonymous,
                'submitted_at' => $this->review->created_at->format('Y-m-d H:i:s'),
            ],
            'created_at' => now()->toISOString()
        ]);
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.User.' . ($this->notifiable?->id ?? 'default'))];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'review-submitted';
    }
}

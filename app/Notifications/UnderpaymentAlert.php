<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

class UnderpaymentAlert extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    /**
     * The alert data for the underpayment.
     */
    protected array $alertData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $alertData)
    {
        $this->alertData = $alertData;
        $this->onQueue('realtime');
        $this->delay(0);
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'underpayment_alert',
            'alert_id' => $this->alertData['alert_id'] ?? null,
            'claim_id' => $this->alertData['claim_id'] ?? null,
            'claim_number' => $this->alertData['claim_number'] ?? 'N/A',
            'expected_amount' => $this->alertData['expected_amount'] ?? 0,
            'paid_amount' => $this->alertData['paid_amount'] ?? 0,
            'variance' => $this->alertData['variance'] ?? 0,
            'title' => 'Underpayment Alert',
            'message' => "Claim {$this->alertData['claim_number']} has underpayment of \${$this->alertData['variance']}",
            'link' => "/admin/claims/{$this->alertData['claim_id']}",
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $claimNumber = $this->alertData['claim_number'] ?? 'N/A';
        $expectedAmount = number_format($this->alertData['expected_amount'] ?? 0, 2);
        $paidAmount = number_format($this->alertData['paid_amount'] ?? 0, 2);
        $variance = number_format($this->alertData['variance'] ?? 0, 2);
        $threshold = $this->alertData['threshold_percentage'] ?? 0;
        $claimId = $this->alertData['claim_id'] ?? '';

        return (new MailMessage)
            ->subject("Underpayment Alert: {$claimNumber}")
            ->greeting('Underpayment Alert')
            ->line("Claim {$claimNumber} has been flagged for underpayment.")
            ->line("Expected Amount: \${$expectedAmount}")
            ->line("Paid Amount: \${$paidAmount}")
            ->line("Variance: \${$variance} ({$threshold}% threshold)")
            ->action('Review Claim', url("/admin/claims/{$claimId}"))
            ->line('Please review this claim to address the underpayment issue.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'underpayment_alert',
            'alert_id' => $this->alertData['alert_id'] ?? null,
            'claim_id' => $this->alertData['claim_id'] ?? null,
            'claim_number' => $this->alertData['claim_number'] ?? 'N/A',
            'expected_amount' => $this->alertData['expected_amount'] ?? 0,
            'paid_amount' => $this->alertData['paid_amount'] ?? 0,
            'variance' => $this->alertData['variance'] ?? 0,
            'threshold_percentage' => $this->alertData['threshold_percentage'] ?? 0,
            'message' => "Claim {$this->alertData['claim_number']} has underpayment of \${$this->alertData['variance']}",
        ];
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        // Use the notifiable property that Laravel sets when sending
        $notifiableId = isset($this->notifiable) ? $this->notifiable->id : null;
        if (!$notifiableId && isset($this->alertData['user_id'])) {
            $notifiableId = $this->alertData['user_id'];
        }
        if (!$notifiableId && isset($this->alertData['userId'])) {
            $notifiableId = $this->alertData['userId'];
        }
        return [new PrivateChannel('App.User.' . ($notifiableId ?? 'default'))];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'underpayment-alert';
    }
}

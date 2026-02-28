<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnderpaymentAlert extends Notification implements ShouldQueue
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
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $claimNumber = $this->alertData['claim_number'];
        $expectedAmount = number_format($this->alertData['expected_amount'], 2);
        $paidAmount = number_format($this->alertData['paid_amount'], 2);
        $variance = number_format($this->alertData['variance'], 2);
        $threshold = $this->alertData['threshold_percentage'];

        return (new MailMessage)
            ->subject("Underpayment Alert: {$claimNumber}")
            ->greeting('Underpayment Alert')
            ->line("Claim {$claimNumber} has been flagged for underpayment.")
            ->line("Expected Amount: \${$expectedAmount}")
            ->line("Paid Amount: \${$paidAmount}")
            ->line("Variance: \${$variance} ({$threshold}% threshold)")
            ->action('Review Claim', url("/admin/claims/{$this->alertData['claim_id']}"))
            ->line('Please review this claim to address the underpayment issue.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'underpayment_alert',
            'alert_id' => $this->alertData['alert_id'],
            'claim_id' => $this->alertData['claim_id'],
            'claim_number' => $this->alertData['claim_number'],
            'expected_amount' => $this->alertData['expected_amount'],
            'paid_amount' => $this->alertData['paid_amount'],
            'variance' => $this->alertData['variance'],
            'threshold_percentage' => $this->alertData['threshold_percentage'],
            'message' => "Claim {$this->alertData['claim_number']} has underpayment of \${$this->alertData['variance']}",
        ];
    }
}

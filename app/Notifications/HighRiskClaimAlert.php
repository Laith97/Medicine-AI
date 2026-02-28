<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HighRiskClaimAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The claim data for the alert.
     */
    protected array $claimData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $claimData)
    {
        $this->claimData = $claimData;
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
        $claimNumber = $this->claimData['claim_number'];
        $denialRisk = number_format($this->claimData['denial_risk'] * 100, 1);
        $expectedAmount = number_format($this->claimData['expected_amount'], 2);

        return (new MailMessage)
            ->subject("High Risk Claim Alert: {$claimNumber}")
            ->greeting('High Risk Claim Alert')
            ->line("Claim {$claimNumber} has been flagged with a high denial risk of {$denialRisk}%.")
            ->line("Expected Amount: \${$expectedAmount}")
            ->when(!empty($this->claimData['top_factors']), function ($mail) {
                $factors = implode(', ', $this->claimData['top_factors']);
                return $mail->line("Top risk factors: {$factors}");
            })
            ->action('Review Claim', url("/admin/claims/{$this->claimData['claim_id']}"))
            ->line('Please review this claim promptly to prevent potential denial.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'high_risk_claim',
            'claim_id' => $this->claimData['claim_id'],
            'claim_number' => $this->claimData['claim_number'],
            'denial_risk' => $this->claimData['denial_risk'],
            'top_factors' => $this->claimData['top_factors'] ?? [],
            'expected_amount' => $this->claimData['expected_amount'],
            'message' => "Claim {$this->claimData['claim_number']} has high denial risk ({$this->claimData['denial_risk']})",
        ];
    }
}

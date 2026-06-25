<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

class HighRiskClaimAlert extends Notification implements ShouldQueue, ShouldBroadcast
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
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'high_risk_claim',
            'claim_id' => $this->claimData['claim_id'] ?? null,
            'claim_number' => $this->claimData['claim_number'] ?? 'N/A',
            'denial_risk' => $this->claimData['denial_risk'] ?? 0,
            'top_factors' => $this->claimData['top_factors'] ?? [],
            'expected_amount' => $this->claimData['expected_amount'] ?? 0,
            'title' => 'High Risk Claim Alert',
            'message' => "Claim {$this->claimData['claim_number']} has high denial risk",
            'link' => "/admin/claims/{$this->claimData['claim_id']}",
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $claimNumber = $this->claimData['claim_number'] ?? 'N/A';
        $denialRisk = number_format(($this->claimData['denial_risk'] ?? 0) * 100, 1);
        $expectedAmount = number_format($this->claimData['expected_amount'] ?? 0, 2);
        $claimId = $this->claimData['claim_id'] ?? '';

        $mail = (new MailMessage)
            ->subject("High Risk Claim Alert: {$claimNumber}")
            ->greeting('High Risk Claim Alert')
            ->line("Claim {$claimNumber} has been flagged with a high denial risk of {$denialRisk}%.")
            ->line("Expected Amount: \${$expectedAmount}");

        if (!empty($this->claimData['top_factors'])) {
            $factors = implode(', ', $this->claimData['top_factors']);
            $mail->line("Top risk factors: {$factors}");
        }

        return $mail->action('Review Claim', url("/admin/claims/{$claimId}"))
            ->line('Please review this claim promptly to prevent potential denial.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'high_risk_claim',
            'claim_id' => $this->claimData['claim_id'] ?? null,
            'claim_number' => $this->claimData['claim_number'] ?? 'N/A',
            'denial_risk' => $this->claimData['denial_risk'] ?? 0,
            'top_factors' => $this->claimData['top_factors'] ?? [],
            'expected_amount' => $this->claimData['expected_amount'] ?? 0,
            'message' => "Claim {$this->claimData['claim_number']} has high denial risk ({$this->claimData['denial_risk']})",
        ];
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        $notifiableId = $this->notifiable?->id ?? $this->claimData['user_id'] ?? 'default';
        return [new PrivateChannel('App.User.' . $notifiableId)];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'high-risk-claim';
    }
}

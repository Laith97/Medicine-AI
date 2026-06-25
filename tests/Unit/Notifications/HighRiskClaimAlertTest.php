<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\HighRiskClaimAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class HighRiskClaimAlertTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected array $claimData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);

        $this->claimData = [
            'claim_id' => 123,
            'claim_number' => 'CLM-2024-001',
            'denial_risk' => 0.85,
            'top_factors' => ['Missing documentation', 'Coding error', 'Timely filing'],
            'expected_amount' => 15000.00,
            'user_id' => $this->admin->id,
        ];
    }

    /** @test */
    public function it_can_be_created()
    {
        $notification = new HighRiskClaimAlert($this->claimData);

        $this->assertInstanceOf(HighRiskClaimAlert::class, $notification);
    }

    /** @test */
    public function it_has_correct_channels()
    {
        $notification = new HighRiskClaimAlert($this->claimData);

        $channels = $notification->via($this->admin);

        $this->assertEquals(['mail', 'database', 'broadcast'], $channels);
    }

    /** @test */
    public function it_has_correct_array_content()
    {
        $notification = new HighRiskClaimAlert($this->claimData);

        $array = $notification->toArray($this->admin);

        $this->assertEquals('high_risk_claim', $array['type']);
        $this->assertEquals(123, $array['claim_id']);
        $this->assertEquals('CLM-2024-001', $array['claim_number']);
        $this->assertEquals(0.85, $array['denial_risk']);
        $this->assertCount(3, $array['top_factors']);
        $this->assertEquals(15000.00, $array['expected_amount']);
        $this->assertStringContainsString('CLM-2024-001', $array['message']);
    }

    /** @test */
    public function it_has_correct_broadcast_content()
    {
        $notification = new HighRiskClaimAlert($this->claimData);

        $broadcast = $notification->toBroadcast($this->admin);

        $this->assertEquals('high_risk_claim', $broadcast->data['type']);
        $this->assertEquals(123, $broadcast->data['claim_id']);
        $this->assertEquals('CLM-2024-001', $broadcast->data['claim_number']);
        $this->assertEquals(0.85, $broadcast->data['denial_risk']);
        $this->assertEquals(15000.00, $broadcast->data['expected_amount']);
        $this->assertStringContainsString('High Risk Claim Alert', $broadcast->data['title']);
        $this->assertStringContainsString('CLM-2024-001', $broadcast->data['message']);
    }

    /** @test */
    public function it_has_correct_mail_content()
    {
        $notification = new HighRiskClaimAlert($this->claimData);

        $mail = $notification->toMail($this->admin);

        $this->assertStringContainsString('High Risk Claim Alert', $mail->subject);
        $this->assertStringContainsString('CLM-2024-001', $mail->subject);
        $this->assertStringContainsString('85.0%', $mail->render()); // denial_risk 0.85 * 100 = 85.0%
        $this->assertStringContainsString('Missing documentation', $mail->render());
        $this->assertStringContainsString('Coding error', $mail->render());
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $notification = new HighRiskClaimAlert($this->claimData);

        $channels = $notification->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('private-App.User.' . $this->admin->id, $channels[0]->name);
    }

    /** @test */
    public function it_broadcasts_with_correct_event_name()
    {
        $notification = new HighRiskClaimAlert($this->claimData);

        $this->assertEquals('high-risk-claim', $notification->broadcastAs());
    }

    /** @test */
    public function it_can_be_sent_via_notification_facade()
    {
        Notification::fake();

        $users = User::factory()->count(3)->create(['role' => 'admin']);

        Notification::send($users, new HighRiskClaimAlert($this->claimData));

        Notification::assertSentTo($users, HighRiskClaimAlert::class);
    }

    /** @test */
    public function it_can_be_stored_in_database()
    {
        $notification = new HighRiskClaimAlert($this->claimData);

        $this->admin->notify($notification);

        $dbNotification = $this->admin->notifications()->first();
        $this->assertNotNull($dbNotification);
        $this->assertEquals(HighRiskClaimAlert::class, $dbNotification->type);

        $data = $dbNotification->data;
        $this->assertEquals('high_risk_claim', $data['type']);
        $this->assertEquals(123, $data['claim_id']);
    }

    /** @test */
    public function it_handles_missing_optional_fields()
    {
        $minimalData = [
            'claim_id' => null,
            'claim_number' => null,
            'denial_risk' => null,
            'top_factors' => [],
            'expected_amount' => null,
        ];

        $notification = new HighRiskClaimAlert($minimalData);

        $array = $notification->toArray($this->admin);

        $this->assertNull($array['claim_id']);
        $this->assertEquals('N/A', $array['claim_number']);
        $this->assertEquals(0, $array['denial_risk']);
        $this->assertEmpty($array['top_factors']);
        $this->assertEquals(0, $array['expected_amount']);
    }

    /** @test */
    public function it_handles_mail_without_factors()
    {
        $data = [
            'claim_id' => 456,
            'claim_number' => 'CLM-2024-002',
            'denial_risk' => 0.5,
            'top_factors' => [],
            'expected_amount' => 5000,
        ];

        $notification = new HighRiskClaimAlert($data);
        $mail = $notification->toMail($this->admin);

        $rendered = $mail->render();
        $this->assertStringContainsString('50.0%', $rendered);
        $this->assertStringContainsString('$5,000.00', $rendered);
    }
}

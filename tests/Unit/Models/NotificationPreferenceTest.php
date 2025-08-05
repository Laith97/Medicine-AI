<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\NotificationPreference;
use App\Models\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $preference = NotificationPreference::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->assertInstanceOf(User::class, $preference->user);
        $this->assertEquals($this->user->id, $preference->user->id);
    }

    /** @test */
    public function it_belongs_to_a_notification_type()
    {
        $notificationType = NotificationType::factory()->create();

        $preference = NotificationPreference::factory()->create([
            'user_id' => $this->user->id,
            'notification_type_id' => $notificationType->id
        ]);

        $this->assertInstanceOf(NotificationType::class, $preference->notificationType);
        $this->assertEquals($notificationType->id, $preference->notificationType->id);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $preference = new NotificationPreference();

        $expected = [
            'user_id',
            'notification_type_id',
            'email_enabled',
            'sms_enabled',
            'in_app_enabled',
            'frequency',
            'is_active',
        ];

        $this->assertEquals($expected, $preference->getFillable());
    }

    /** @test */
    public function it_has_correct_cast_attributes()
    {
        $preference = new NotificationPreference();

        $casts = $preference->getCasts();

        $this->assertEquals('boolean', $casts['email_enabled']);
        $this->assertEquals('boolean', $casts['sms_enabled']);
        $this->assertEquals('boolean', $casts['in_app_enabled']);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    /** @test */
    public function it_can_be_created_with_default_values()
    {
        $preference = NotificationPreference::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->assertTrue($preference->email_enabled);
        $this->assertTrue($preference->sms_enabled);
        $this->assertTrue($preference->in_app_enabled);
        $this->assertEquals('immediate', $preference->frequency);
        $this->assertTrue($preference->is_active);
    }

    /** @test */
    public function it_can_check_if_all_channels_are_enabled()
    {
        $preference = NotificationPreference::factory()->create([
            'email_enabled' => true,
            'sms_enabled' => true,
            'in_app_enabled' => true
        ]);

        $this->assertTrue($preference->allChannelsEnabled());
    }

    /** @test */
    public function it_can_check_if_all_channels_are_disabled()
    {
        $preference = NotificationPreference::factory()->create([
            'email_enabled' => false,
            'sms_enabled' => false,
            'in_app_enabled' => false
        ]);

        $this->assertTrue($preference->allChannelsDisabled());
    }

    /** @test */
    public function it_can_check_if_specific_channel_is_enabled()
    {
        $preference = NotificationPreference::factory()->create([
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true
        ]);

        $this->assertTrue($preference->isChannelEnabled('email'));
        $this->assertFalse($preference->isChannelEnabled('sms'));
        $this->assertTrue($preference->isChannelEnabled('in_app'));
    }

    /** @test */
    public function it_can_enable_specific_channel()
    {
        $preference = NotificationPreference::factory()->create([
            'email_enabled' => false,
            'sms_enabled' => false,
            'in_app_enabled' => false
        ]);

        $preference->enableChannel('email');
        $preference->save();

        $this->assertTrue($preference->fresh()->email_enabled);
        $this->assertFalse($preference->fresh()->sms_enabled);
        $this->assertFalse($preference->fresh()->in_app_enabled);
    }

    /** @test */
    public function it_can_disable_specific_channel()
    {
        $preference = NotificationPreference::factory()->create([
            'email_enabled' => true,
            'sms_enabled' => true,
            'in_app_enabled' => true
        ]);

        $preference->disableChannel('email');
        $preference->save();

        $this->assertFalse($preference->fresh()->email_enabled);
        $this->assertTrue($preference->fresh()->sms_enabled);
        $this->assertTrue($preference->fresh()->in_app_enabled);
    }

    /** @test */
    public function it_can_toggle_channel()
    {
        $preference = NotificationPreference::factory()->create([
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true
        ]);

        $preference->toggleChannel('email'); // Disable
        $preference->toggleChannel('sms');   // Enable

        $this->assertFalse($preference->fresh()->email_enabled);
        $this->assertTrue($preference->fresh()->sms_enabled);
        $this->assertTrue($preference->fresh()->in_app_enabled);
    }

    /** @test */
    public function it_can_get_enabled_channels()
    {
        $preference = NotificationPreference::factory()->create([
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true
        ]);

        $enabledChannels = $preference->getEnabledChannels();

        $this->assertContains('email', $enabledChannels);
        $this->assertNotContains('sms', $enabledChannels);
        $this->assertContains('in_app', $enabledChannels);
        $this->assertCount(2, $enabledChannels);
    }

    /** @test */
    public function it_can_set_frequency()
    {
        $preference = NotificationPreference::factory()->create([
            'user_id' => $this->user->id
        ]);

        $validFrequencies = ['immediate', 'daily', 'weekly', 'monthly'];

        foreach ($validFrequencies as $frequency) {
            $preference->setFrequency($frequency);
            $this->assertEquals($frequency, $preference->fresh()->frequency);
        }
    }

    /** @test */
    public function it_validates_frequency_values()
    {
        $preference = NotificationPreference::factory()->create([
            'user_id' => $this->user->id
        ]);

        $invalidFrequencies = ['invalid', 'hourly', 'yearly', ''];

        foreach ($invalidFrequencies as $frequency) {
            $this->expectException(\InvalidArgumentException::class);
            $preference->setFrequency($frequency);
        }
    }

    /** @test */
    public function it_can_activate_and_deactivate()
    {
        $preference = NotificationPreference::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);

        $preference->deactivate();
        $this->assertFalse($preference->fresh()->is_active);

        $preference->activate();
        $this->assertTrue($preference->fresh()->is_active);
    }

    /** @test */
    public function it_can_check_if_active()
    {
        $activePreference = NotificationPreference::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);

        $inactivePreference = NotificationPreference::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false
        ]);

        $this->assertTrue($activePreference->isActive());
        $this->assertFalse($inactivePreference->isActive());
    }

    /** @test */
    public function it_has_scope_for_active_preferences()
    {
        NotificationPreference::factory()->create(['is_active' => true]);
        NotificationPreference::factory()->create(['is_active' => false]);
        NotificationPreference::factory()->create(['is_active' => true]);

        $activeCount = NotificationPreference::active()->count();

        $this->assertEquals(2, $activeCount);
    }

    /** @test */
    public function it_has_scope_for_inactive_preferences()
    {
        NotificationPreference::factory()->create(['is_active' => true]);
        NotificationPreference::factory()->create(['is_active' => false]);
        NotificationPreference::factory()->create(['is_active' => false]);

        $inactiveCount = NotificationPreference::inactive()->count();

        $this->assertEquals(2, $inactiveCount);
    }

    /** @test */
    public function it_has_scope_for_user_preferences()
    {
        $user2 = User::factory()->create();

        NotificationPreference::factory()->create(['user_id' => $this->user->id]);
        NotificationPreference::factory()->create(['user_id' => $user2->id]);
        NotificationPreference::factory()->create(['user_id' => $this->user->id]);

        $userPreferencesCount = NotificationPreference::forUser($this->user)->count();

        $this->assertEquals(2, $userPreferencesCount);
    }

    /** @test */
    public function it_can_get_user_preferences_as_array()
    {
        $preference = NotificationPreference::factory()->create([
            'user_id' => $this->user->id,
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true,
            'frequency' => 'daily'
        ]);

        $preferencesArray = $preference->toArray();

        $this->assertArrayHasKey('email_enabled', $preferencesArray);
        $this->assertArrayHasKey('sms_enabled', $preferencesArray);
        $this->assertArrayHasKey('in_app_enabled', $preferencesArray);
        $this->assertArrayHasKey('frequency', $preferencesArray);
        $this->assertArrayHasKey('is_active', $preferencesArray);
    }
}

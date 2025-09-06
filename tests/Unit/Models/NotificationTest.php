<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_belongs_to_a_notifiable_user()
    {
        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $this->user->id
        ]);

        $this->assertInstanceOf(User::class, $notification->notifiable);
        $this->assertEquals($this->user->id, $notification->notifiable->id);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $notification = new Notification();

        $expected = [
            'notifiable_type',
            'notifiable_id',
            'type',
            'title',
            'message',
            'icon',
            'link',
            'link_text',
            'related_type',
            'related_id',
            'is_read',
            'read_at',
            'data',
        ];

        $this->assertEquals($expected, $notification->getFillable());
    }

    /** @test */
    public function it_has_correct_cast_attributes()
    {
        $notification = new Notification();

        $casts = $notification->getCasts();

        $this->assertEquals('boolean', $casts['is_read']);
        $this->assertEquals('datetime', $casts['read_at']);
        $this->assertEquals('array', $casts['data']);
    }

    /** @test */
    public function it_can_mark_as_read()
    {
        $notification = Notification::factory()->create([
            'is_read' => false,
            'read_at' => null
        ]);

        $notification->markAsRead();

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_can_mark_as_unread()
    {
        $notification = Notification::factory()->create([
            'is_read' => true,
            'read_at' => now()
        ]);

        $notification->markAsUnread();

        $this->assertFalse($notification->fresh()->is_read);
        $this->assertNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_has_scope_for_unread_notifications()
    {
        Notification::factory()->create(['is_read' => false]);
        Notification::factory()->create(['is_read' => true]);
        Notification::factory()->create(['is_read' => false]);

        $unreadCount = Notification::unread()->count();

        $this->assertEquals(2, $unreadCount);
    }

    /** @test */
    public function it_has_scope_for_read_notifications()
    {
        Notification::factory()->create(['is_read' => false]);
        Notification::factory()->create(['is_read' => true]);
        Notification::factory()->create(['is_read' => true]);

        $readCount = Notification::read()->count();

        $this->assertEquals(2, $readCount);
    }

    /** @test */
    public function it_has_scope_for_specific_notification_type()
    {
        Notification::factory()->create(['type' => 'appointment_booked']);
        Notification::factory()->create(['type' => 'diagnosis_submitted']);
        Notification::factory()->create(['type' => 'appointment_booked']);

        $appointmentCount = Notification::type('appointment_booked')->count();

        $this->assertEquals(2, $appointmentCount);
    }

    /** @test */
    public function it_has_scope_for_notifications_related_to_a_model()
    {
        $appointment = \App\Models\Appointment::factory()->create();

        Notification::factory()->create([
            'related_type' => 'App\\Models\\Appointment',
            'related_id' => $appointment->id
        ]);

        Notification::factory()->create([
            'related_type' => 'App\\Models\\Diagnosis',
            'related_id' => 1
        ]);

        $relatedCount = Notification::relatedTo('App\\Models\\Appointment', $appointment->id)->count();

        $this->assertEquals(1, $relatedCount);
    }

    /** @test */
    public function it_has_scope_for_specific_user_notifications()
    {
        $user2 = User::factory()->create();

        Notification::factory()->create([
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $this->user->id
        ]);

        Notification::factory()->create([
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $user2->id
        ]);

        $userNotificationsCount = Notification::forUser($this->user)->count();

        $this->assertEquals(1, $userNotificationsCount);
    }

    /** @test */
    public function it_returns_correct_icon_class_for_different_types()
    {
        $notifications = [
            ['icon' => 'success', 'expected' => 'fas fa-check-circle text-success'],
            ['icon' => 'warning', 'expected' => 'fas fa-exclamation-triangle text-warning'],
            ['icon' => 'error', 'expected' => 'fas fa-times-circle text-danger'],
            ['icon' => 'info', 'expected' => 'fas fa-info-circle text-info'],
            ['icon' => 'default', 'expected' => 'fas fa-bell text-info'],
        ];

        foreach ($notifications as $notification) {
            $model = Notification::factory()->create(['icon' => $notification['icon']]);
            $this->assertEquals($notification['expected'], $model->getIconClass());
        }
    }

    /** @test */
    public function it_creates_notification_with_correct_data_structure()
    {
        $data = [
            'title' => 'Test Notification',
            'message' => 'This is a test message',
            'icon' => 'info',
            'link' => '/test-link',
            'link_text' => 'View Details'
        ];

        $notification = Notification::factory()->create([
            'data' => json_encode($data)
        ]);

        $this->assertEquals($data['title'], $notification->data['title']);
        $this->assertEquals($data['message'], $notification->data['message']);
        $this->assertEquals($data['icon'], $notification->data['icon']);
        $this->assertEquals($data['link'], $notification->data['link']);
        $this->assertEquals($data['link_text'], $notification->data['link_text']);
    }

    /** @test */
    public function it_can_be_created_without_data_field()
    {
        $notification = Notification::factory()->create(['data' => null]);

        $this->assertNull($notification->data);
        $this->assertIsArray($notification->data); // Cast to array
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $notification = Notification::factory()->create();

        $this->assertIsString($notification->id);
        $this->assertEquals(36, strlen($notification->id)); // UUID length
    }

    /** @test */
    public function it_has_morph_to_relationship()
    {
        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $this->user->id
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $notification->notifiable());
        $this->assertEquals($this->user, $notification->notifiable);
    }
}

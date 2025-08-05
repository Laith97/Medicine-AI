<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTypeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $notificationType = new NotificationType();

        $expected = [
            'name',
            'description',
            'icon',
            'color',
            'is_active',
            'default_settings',
        ];

        $this->assertEquals($expected, $notificationType->getFillable());
    }

    /** @test */
    public function it_has_correct_cast_attributes()
    {
        $notificationType = new NotificationType();

        $casts = $notificationType->getCasts();

        $this->assertEquals('boolean', $casts['is_active']);
        $this->assertEquals('array', $casts['default_settings']);
    }

    /** @test */
    public function it_can_be_created_with_default_values()
    {
        $notificationType = NotificationType::create([
            'name' => 'Test Notification',
            'description' => 'Test description'
        ]);

        $this->assertEquals('Test Notification', $notificationType->name);
        $this->assertEquals('Test description', $notificationType->description);
        $this->assertEquals('info', $notificationType->icon);
        $this->assertEquals('#007bff', $notificationType->color);
        $this->assertTrue($notificationType->is_active);
        $this->assertEquals([
            'email_enabled' => true,
            'sms_enabled' => true,
            'in_app_enabled' => true,
            'frequency' => 'immediate'
        ], $notificationType->default_settings);
    }

    /** @test */
    public function it_can_be_created_with_custom_values()
    {
        $defaultSettings = [
            'email_enabled' => false,
            'sms_enabled' => true,
            'in_app_enabled' => false,
            'frequency' => 'daily'
        ];

        $notificationType = NotificationType::create([
            'name' => 'Custom Notification',
            'description' => 'Custom description',
            'icon' => 'warning',
            'color' => '#ff9800',
            'is_active' => false,
            'default_settings' => $defaultSettings
        ]);

        $this->assertEquals('Custom Notification', $notificationType->name);
        $this->assertEquals('warning', $notificationType->icon);
        $this->assertEquals('#ff9800', $notificationType->color);
        $this->assertFalse($notificationType->is_active);
        $this->assertEquals($defaultSettings, $notificationType->default_settings);
    }

    /** @test */
    public function it_has_scope_for_active_types()
    {
        NotificationType::factory()->create(['is_active' => true]);
        NotificationType::factory()->create(['is_active' => false]);
        NotificationType::factory()->create(['is_active' => true]);

        $activeCount = NotificationType::active()->count();

        $this->assertEquals(2, $activeCount);
    }

    /** @test */
    public function it_has_scope_for_inactive_types()
    {
        NotificationType::factory()->create(['is_active' => true]);
        NotificationType::factory()->create(['is_active' => false]);
        NotificationType::factory()->create(['is_active' => false]);

        $inactiveCount = NotificationType::inactive()->count();

        $this->assertEquals(2, $inactiveCount);
    }

    /** @test */
    public function it_has_scope_for_types_by_icon()
    {
        NotificationType::factory()->create(['icon' => 'success']);
        NotificationType::factory()->create(['icon' => 'warning']);
        NotificationType::factory()->create(['icon' => 'success']);

        $successCount = NotificationType::byIcon('success')->count();

        $this->assertEquals(2, $successCount);
    }

    /** @test */
    public function it_can_get_default_setting()
    {
        $defaultSettings = [
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true,
            'frequency' => 'daily'
        ];

        $notificationType = NotificationType::factory()->create([
            'default_settings' => $defaultSettings
        ]);

        $this->assertTrue($notificationType->getDefaultSetting('email_enabled'));
        $this->assertFalse($notificationType->getDefaultSetting('sms_enabled'));
        $this->assertEquals('daily', $notificationType->getDefaultSetting('frequency'));
        $this->assertNull($notificationType->getDefaultSetting('nonexistent'));
    }

    /** @test */
    public function it_can_set_default_setting()
    {
        $notificationType = NotificationType::factory()->create();

        $notificationType->setDefaultSetting('email_enabled', false);
        $notificationType->setDefaultSetting('frequency', 'weekly');

        $this->assertFalse($notificationType->fresh()->default_settings['email_enabled']);
        $this->assertEquals('weekly', $notificationType->fresh()->default_settings['frequency']);
    }

    /** @test */
    public function it_can_merge_default_settings()
    {
        $originalSettings = [
            'email_enabled' => true,
            'sms_enabled' => true,
            'frequency' => 'immediate'
        ];

        $notificationType = NotificationType::factory()->create([
            'default_settings' => $originalSettings
        ]);

        $newSettings = [
            'sms_enabled' => false,
            'in_app_enabled' => true,
            'frequency' => 'daily'
        ];

        $notificationType->mergeDefaultSettings($newSettings);

        $expectedSettings = [
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true,
            'frequency' => 'daily'
        ];

        $this->assertEquals($expectedSettings, $notificationType->fresh()->default_settings);
    }

    /** @test */
    public function it_can_get_icon_class()
    {
        $notificationTypes = [
            ['icon' => 'success', 'expected' => 'fas fa-check-circle text-success'],
            ['icon' => 'warning', 'expected' => 'fas fa-exclamation-triangle text-warning'],
            ['icon' => 'error', 'expected' => 'fas fa-times-circle text-danger'],
            ['icon' => 'info', 'expected' => 'fas fa-info-circle text-info'],
            ['icon' => 'default', 'expected' => 'fas fa-bell text-info'],
        ];

        foreach ($notificationTypes as $type) {
            $model = NotificationType::factory()->create(['icon' => $type['icon']]);
            $this->assertEquals($type['expected'], $model->getIconClass());
        }
    }

    /** @test */
    public function it_can_get_color_class()
    {
        $notificationTypes = [
            ['color' => '#28a745', 'expected' => 'text-success'],
            ['color' => '#ffc107', 'expected' => 'text-warning'],
            ['color' => '#dc3545', 'expected' => 'text-danger'],
            ['color' => '#17a2b8', 'expected' => 'text-info'],
            ['color' => '#6c757d', 'expected' => 'text-secondary'],
        ];

        foreach ($notificationTypes as $type) {
            $model = NotificationType::factory()->create(['color' => $type['color']]);
            $this->assertEquals($type['expected'], $model->getColorClass());
        }
    }

    /** @test */
    public function it_can_activate_and_deactivate()
    {
        $notificationType = NotificationType::factory()->create([
            'is_active' => true
        ]);

        $notificationType->deactivate();
        $this->assertFalse($notificationType->fresh()->is_active);

        $notificationType->activate();
        $this->assertTrue($notificationType->fresh()->is_active);
    }

    /** @test */
    public function it_can_check_if_active()
    {
        $activeType = NotificationType::factory()->create(['is_active' => true]);
        $inactiveType = NotificationType::factory()->create(['is_active' => false]);

        $this->assertTrue($activeType->isActive());
        $this->assertFalse($inactiveType->isActive());
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        NotificationType::create([
            'description' => 'Missing name field'
        ]);
    }

    /** @test */
    public function it_has_unique_name_constraint()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        NotificationType::create(['name' => 'Duplicate Name', 'description' => 'First']);
        NotificationType::create(['name' => 'Duplicate Name', 'description' => 'Second']);
    }

    /** @test */
    public function it_can_get_all_active_types_as_array()
    {
        NotificationType::factory()->create(['name' => 'Active 1', 'is_active' => true]);
        NotificationType::factory()->create(['name' => 'Active 2', 'is_active' => true]);
        NotificationType::factory()->create(['name' => 'Inactive', 'is_active' => false]);

        $activeTypes = NotificationType::getActiveTypesArray();

        $this->assertCount(2, $activeTypes);
        $this->assertArrayHasKey('Active 1', $activeTypes);
        $this->assertArrayHasKey('Active 2', $activeTypes);
        $this->assertArrayNotHasKey('Inactive', $activeTypes);
    }

    /** @test */
    public function it_can_get_all_types_for_select()
    {
        NotificationType::factory()->create(['name' => 'Type 1', 'is_active' => true]);
        NotificationType::factory()->create(['name' => 'Type 2', 'is_active' => true]);
        NotificationType::factory()->create(['name' => 'Type 3', 'is_active' => false]);

        $selectOptions = NotificationType::getSelectOptions();

        $this->assertCount(2, $selectOptions);
        $this->assertEquals('Type 1', $selectOptions[0]['label']);
        $this->assertEquals('Type 2', $selectOptions[1]['label']);
    }
}

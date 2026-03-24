<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MenuHelper;
use Tests\TestCase;

class MenuHelperTest extends TestCase
{
    /**
     * Test getSmsConfigMenuItem returns correct structure without permission
     */
    public function test_get_sms_config_menu_item_without_permission(): void
    {
        $item = MenuHelper::getSmsConfigMenuItem();

        $this->assertIsArray($item);
        $this->assertEquals('SMS Configuration', $item['name']);
        $this->assertEquals('sms.config.index', $item['route']);
        $this->assertEquals('fas fa-sms', $item['icon']);
        $this->assertArrayNotHasKey('permission', $item);
    }

    /**
     * Test getSmsConfigMenuItem returns correct structure with permission
     */
    public function test_get_sms_config_menu_item_with_permission(): void
    {
        $item = MenuHelper::getSmsConfigMenuItem('settings');

        $this->assertIsArray($item);
        $this->assertEquals('SMS Configuration', $item['name']);
        $this->assertEquals('sms.config.index', $item['route']);
        $this->assertEquals('fas fa-sms', $item['icon']);
        $this->assertEquals('settings', $item['permission']);
    }

    /**
     * Test getSmsConfigMenuItem with different permission
     */
    public function test_get_sms_config_menu_item_with_different_permission(): void
    {
        $item = MenuHelper::getSmsConfigMenuItem('billing');

        $this->assertEquals('billing', $item['permission']);
    }
}

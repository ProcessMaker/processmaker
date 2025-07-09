<?php

namespace Tests\Unit;

use Database\Seeders\ScreenEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use Tests\TestCase;

class ScreenEmailSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_screen_without_system_flag()
    {
        $seeder = new ScreenEmailSeeder();
        $seeder->run();

        $screen = Screen::where('key', 'default-email-task-notification')->first();

        $this->assertNotNull($screen);
        $this->assertEquals('default-email-task-notification', $screen->key);

        $systemCategory = ScreenCategory::where('name', 'System')->first();
        if ($systemCategory) {
            $this->assertFalse($screen->categories()->where('category_id', $systemCategory->id)->exists());
        }

        $this->assertNull($screen->screen_category_id);
    }

    public function test_get_screen_by_key_non_system_method()
    {
        $screen = Screen::getScreenByKeyNonSystem('default-email-task-notification');

        $this->assertNotNull($screen);
        $this->assertEquals('default-email-task-notification', $screen->key);

        $systemCategory = ScreenCategory::where('name', 'System')->first();
        if ($systemCategory) {
            $this->assertFalse($screen->categories()->where('category_id', $systemCategory->id)->exists());
        }

        $this->assertNull($screen->screen_category_id);
    }
}

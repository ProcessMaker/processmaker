<?php

use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddDefaultScreensToSystemCategory extends Upgrade
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->assignToSystemCategory('default-display-screen');
        $this->assignToSystemCategory('default-form-screen');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }

    /**
     * Assign system screens to system category.
     */
    private function assignToSystemCategory(string $key)
    {
        $screens = Screen::where('key', $key)->get();
        $screenCategory = ScreenCategory::where('name', 'System')
            ->where('is_system', 1)
            ->firstOrFail();

        foreach ($screens as $screen) {
            $screen->categories()->attach([$screenCategory->id]);
        }
    }
}

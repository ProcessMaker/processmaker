<?php

namespace Tests\Feature\ImportExport\Exporters;

use ProcessMaker\ImportExport\Export;
use ProcessMaker\Models\Screen;
use Tests\TestCase;

class ExportTest extends TestCase
{
    public function testScreenExport()
    {
        $screen = $this->createScreen();
        $result = Export::exportScreen($screen->id);
    }
    
    private function createScreen($title = 'screen', $addWatchers = true)
    {
        $config = json_decode(file_get_contents(__DIR__ . '/fixtures/screen_with_nested_screen.json'), true);
        $watchers = $addWatchers ? json_decode(file_get_contents(__DIR__ . '/fixtures/watchers.json'), true) : [];
        $screen = Screen::factory()->create([
            'title' => $title,
            'config' => $config,
            'watchers' => $watchers,
        ]);

        return $screen;
    }
}

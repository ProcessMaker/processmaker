<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Export;
use ProcessMaker\Models\Screen;
use Tests\TestCase;

class ExportTest extends TestCase
{
    public function testScreenExport()
    {
        $assets = $this->runExport();

        $this->assertEquals('screen', $assets['Screen_' . $this->screen->id]->payload['title']);
        $this->assertEquals('nested screen', $assets['Screen_' . $this->nestedScreen->id]->payload['title']);
        $this->assertEquals('nested nested screen', $assets['Screen_' . $this->nestedNestedScreen->id]->payload['title']);
    }
    
    public function testScreenImport()
    {
        $assets = $this->runExport();
        $this->screen->delete();
        $this->nestedScreen->delete();
        $this->nestedNestedScreen->delete();


    }

    private function runExport()
    {
        $this->screen = $this->createScreen();
        $this->nestedScreen = $this->createScreen('nested screen', false);
        $this->nestedNestedScreen = Screen::factory()->create(['title' => 'nested nested screen', 'config' => []]);
        $this->associateNestedScreen($this->nestedScreen, $this->nestedNestedScreen);
        $this->associateNestedScreen($this->screen, $this->nestedScreen);

        $result = Export::exportScreen($this->screen->id);

        return $result->toArray();
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
    
    private function associateNestedScreen($parent, $child)
    {
        $config = $parent->config;
        Arr::set($config, '0.items.2.config.screen', $child->id);
        $parent->config = $config;
        $parent->saveOrFail();
    }
}

<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Strategies\ScreenConfig;
use Tests\TestCase;

class ScreenConfigTest extends TestCase
{
    public function testExport()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../fixtures/screen_with_nested_screen.json'), true);
        $this->assertEquals('0.items.2.component', $result[0]['path']);
    }   
   
}

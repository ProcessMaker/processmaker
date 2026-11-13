<?php

namespace Tests\Unit\Managers;

use Illuminate\Support\HtmlString;
use ProcessMaker\Managers\ModelerManager;
use Tests\TestCase;

class ModelerManagerTest extends TestCase
{
    public function testAddScriptRegistersEachUrlOnce(): void
    {
        $manager = new ModelerManager();

        $manager->addScript(new HtmlString('/vendor/x/js/modeler-test.js?id=1'));
        $manager->addScript('/vendor/x/js/modeler-test.js?id=1');
        $manager->addScript('/vendor/x/js/modeler-test.js?id=1', ['type' => 'module']);
        $manager->addScript('/vendor/x/js/modeler-test.js?id=2');

        $srcs = array_column($manager->getScriptWithParams(), 'src');
        $matching = array_values(array_filter($srcs, fn ($src) => str_contains((string) $src, '/x/js/modeler-test.js')));

        $this->assertCount(2, $matching);
        $this->assertSame(
            ['/vendor/x/js/modeler-test.js?id=1', '/vendor/x/js/modeler-test.js?id=2'],
            array_map('strval', $matching)
        );
        $this->assertCount(count($manager->getScripts()), $manager->getScriptWithParams());
    }
}

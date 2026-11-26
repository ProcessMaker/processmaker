<?php

namespace ProcessMaker\Managers;

use Tests\TestCase;

class GlobalScriptsManagerTest extends TestCase
{
    public function testAddScriptIgnoresDuplicates()
    {
        $manager = new GlobalScriptsManager();

        $manager->addScript('/vendor/processmaker/packages/package-a/js/global.js');
        $manager->addScript('/vendor/processmaker/packages/package-a/js/global.js');
        $manager->addScript('/vendor/processmaker/packages/package-b/js/global.js');

        $this->assertSame([
            '/vendor/processmaker/packages/package-a/js/global.js',
            '/vendor/processmaker/packages/package-b/js/global.js',
        ], $manager->getScripts());
    }
}

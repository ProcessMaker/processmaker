<?php

namespace Tests\Managers;

use DOMXPath;
use ProcessMaker\Managers\PMConfigGenericExportManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Providers\WorkflowServiceProvider;
use Tests\TestCase;

class PMConfigGenericExportManagerTest extends TestCase
{
    public function test()
    {
        //
        //
        //
        $this->markTestSkipped();
        //
        //
        //

        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/pm_config_generic_export.bpmn'),
        ]);

        $manager = new PMConfigGenericExportManager(
            'bpmn:callActivity', Screen::class, ['screenRef', 'anotherScreenRef']
        );
        $result = $manager->referencesToExport($process);
        $this->assertEquals([
            [Screen::class, 9991],
            [Screen::class, 9992],
            [Screen::class, 9993],
            [Screen::class, 9994],
        ], $result);

        $abeScreen = Screen::factory()->create();
        $abeAnotherScreen = Screen::factory()->create();
        $anotherAbeScreen = Screen::factory()->create();
        $anotherAbeAnotherScreen = Screen::factory()->create();
        $references = [
            Screen::class => [
                9991 => $abeScreen,
                9992 => $abeAnotherScreen,
                9993 => $anotherAbeScreen,
                9994 => $anotherAbeAnotherScreen,
            ],
        ];
        $manager->updateReferences($process, $references);

        $definitions = $process->refresh()->getDefinitions();
        $xpath = new DOMXPath($definitions);
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        $xpath->registerNamespace('bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL');

        $nodes = $xpath->query('//bpmn:callActivity');
        $abeConfig = json_decode(
            $nodes[0]->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config')
        );
        $anotherAbeConfig = json_decode(
            $nodes[1]->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'config')
        );
        $this->assertEquals($abeScreen->id, $abeConfig->screenRef);
        $this->assertEquals($abeAnotherScreen->id, $abeConfig->anotherScreenRef);
        $this->assertEquals($anotherAbeScreen->id, $anotherAbeConfig->screenRef);
        $this->assertEquals($anotherAbeAnotherScreen->id, $anotherAbeConfig->anotherScreenRef);
    }
}

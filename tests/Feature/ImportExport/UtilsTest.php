<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
use Tests\TestCase;

class UtilsTest extends TestCase
{
    public $bpmn;

    public $process;

    public function setUpProcess()
    {
        $this->bpmn = file_get_contents(__DIR__ . '/fixtures/process-with-pm-config.bpmn.xml');
        $this->process = Process::factory()->create(['bpmn' => $this->bpmn]);
    }

    public function testGetServiceTasks()
    {
        $result = Utils::getServiceTasks($this->process, 'package-data-sources/data-source-task-service');
        $this->assertCount(1, $result);
        $this->assertEquals('Data Connector', $result->first()->getAttribute('name'));
    }

    public function testGetPmConfig()
    {
        $result = Utils::getServiceTasks($this->process, 'package-data-sources/data-source-task-service');
        $config = Utils::getPmConfig($result->first());
        $this->assertEquals('list', $config['endpoint']);
    }

    public function testFindScreenDependent()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/fixtures/screen-with-dependents-min.json'), true);
        $matches = Utils::findScreenDependent($config, 'MyComponent', 'test');

        $this->assertCount(6, $matches);

        foreach (['first', 'second', 'third', 'fourth', 'fifth', 'sixth'] as $i => $nth) {
            $this->assertEquals($matches[$i]['value'], $nth);
            $this->assertEquals(Arr::get($config, $matches[$i]['path']), $nth);
            $component = Arr::get($config, $matches[$i]['component_path']);
            $this->assertEquals($component['component'], 'MyComponent');
        }
    }
}

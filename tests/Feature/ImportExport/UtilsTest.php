<?php

namespace Tests\Feature\ImportExport\Exporters;

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
        $this->process = factory(Process::class)->create(['bpmn' => $this->bpmn]);
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
}

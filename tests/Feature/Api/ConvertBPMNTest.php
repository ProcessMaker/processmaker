<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Nayra\Storage\BpmnDocument;

/**
 * Tests routes related to processes / CRUD related methods
 *
 * @group process_tests
 */
class ConvertBPMNTest extends TestCase
{
    use WithFaker;
    use RequestHelper;
    use ResourceAssertionsTrait;

    public $withPermissions = true;

    /**
     * Test convert subProcess to callActivity
     */
    public function testConvertSubProcess()
    {
        $process = factory(Process::class)->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/CP.01.00 Create new customer.bpmn'),
        ]);
        $this->assertNotContains('subProcess', $process->bpmn);
        $this->assertEquals(2, Process::count());
    }

    /**
     * Test convert sendTask to scriptTask
     */
    public function testConvertSendTask()
    {
        $process = factory(Process::class)->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/adonis.bpmn'),
        ]);
        $this->assertNotContains('sendTask', $process->bpmn);

        $document = new BpmnDocument();
        $document->loadXML($process->bpmn);
        $validation = $document->validateBPMNSchema(public_path('definitions/ProcessMaker.xsd'));
    }
}

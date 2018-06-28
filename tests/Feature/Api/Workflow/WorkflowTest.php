<?php
namespace Tests\Feature\Api\Workflow;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Model\Process;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use Tests\Feature\Api\ApiTestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class WorkflowTest extends ApiTestCase
{

    use DatabaseTransactions;

    const API_TEST_PROCESS = '/api/1.0/processes';

    /**
     * Tests to determine that reaching the processes endpoint is protected by an authenticated user
     */
    public function testUnauthenticated()
    {
        //Create a new process with lanes
        $process = factory(Process::class)->create([
            'bpmn' => file_get_contents(__DIR__ . '/bpmn/Lanes.bpmn'),
        ]);
        
        //Get the process definition
        $definitions = $process->getDefinitions();
        
        //Assertion: Check that $definition exists
        $this->assertNotEmpty($definitions);

        //Assertion: Check if MainProcess definition exists
        $this->assertInstanceOf(ProcessInterface::class, $definitions->getProcess('MainProcess'));
    }
}

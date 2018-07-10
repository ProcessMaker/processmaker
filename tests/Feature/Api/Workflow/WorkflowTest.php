<?php
namespace Tests\Feature\Api\Workflow;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use Tests\Feature\Api\ApiTestCase;

/**
 * Tests of Nayra engine
 *
 */
class WorkflowTest extends ApiTestCase
{

    use DatabaseTransactions;

    const API_TRIGGER_START_EVENT = '/api/1.0/processes/%s/events/%s/trigger';
    const API_COMPLETE_DELEGATION = '/api/1.0/processes/%s/instances/%s/tokens/%s/complete';

    /**
     * Tests access to definitions in a process row.
     *
     */
    public function testAccessToDefinitions()
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

    /**
     * Tests to start a process and complete a task.
     *
     */
    public function testStartProcessAndCompleteTask()
    {
        $this->login();

        //Create a new process with lanes
        $process = factory(Process::class)->create([
            'bpmn' => file_get_contents(__DIR__ . '/bpmn/Lanes.bpmn'),
        ]);

        //Start event id:
        $eventId = 'StartEvent';

        //Initial data
        $data = [
            'startDate' => '',
            'endDate' => '',
            'comments' => '',
        ];
        
        //Trigger the start event
        $response = $this->api(
            'POST',
            sprintf(
                self::API_TRIGGER_START_EVENT,
                $process->uid,
                $eventId
            ),
            $data
        );
        $response->assertStatus(200);

        //Assertion: Check that one instance was created
        $this->assertEquals(1, $process->cases()->count());
        $instance = $process->cases()->first();

        //Assertion: Check that one token was created
        $this->assertEquals(1, $instance->delegations()->count());

        //Ge the instance and the token
        $token = $instance->delegations()->first();

        //Complete a delegation
        $response = $this->api(
            'POST',
            sprintf(
                self::API_COMPLETE_DELEGATION,
                $process->uid,
                $instance->uid,
                $token->uid
            ),
            $data
        );
        $response->assertStatus(200);

        //Assertion: Verifica que ahora existen dos tokens
        $this->assertEquals(2, $instance->delegations()->count());

        //Assertion: Verifica que el primer token esta CLOSED
        $tokens = $instance->delegations()->orderBy('id')->get();
        
        $this->assertEquals('CLOSED', $tokens[0]->thread_status);

        //Assertion: Verifica que el segundo token esta ACTIVE
        $this->assertEquals('ACTIVE', $tokens[1]->thread_status);
    }
    
    private function login()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $this->auth($admin->username, 'password');
    }
}

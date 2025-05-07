<?php

namespace Tests\Feature\Cases;

use Database\Factories\CaseStartedFactory;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Mockery;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseRepository;
use ProcessMaker\Repositories\CaseTaskRepository;
use Tests\TestCase;

class CasesTaskTest extends TestCase
{
    protected $user;

    protected $process;

    protected $instance;

    protected $token;

    protected $caseNumber;

    protected $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process = Process::factory()->create();
        $this->user = User::factory()->create();
        $this->instance = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $this->process->id,
        ]);

        $this->token = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->instance->id,
            'element_type' => 'task',
            'status' => 'ACTIVE'
        ]);

        $this->caseNumber = 1234;
        $this->task = (object)[
            'id' => 'task-1',
            'status' => 'ACTIVE'
        ];
    }
    public function test_update_case_started_task_status()
    {
        $repo = new CaseRepository();
        $repo->create($this->instance);
        $repo->update($this->instance, $this->token);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->instance->case_number,
            'tasks->[0]->id' => $this->token->id,
            'tasks->[0]->element_id' => $this->token->element_id,
            'tasks->[0]->name' => $this->token->element_name,
            'tasks->[0]->process_id' => $this->token->process_id,
            'tasks->[0]->status' => $this->token->status,
        ]);

        $this->token->status = 'COMPLETED';

        $taskRepo = new CaseTaskRepository($this->instance->case_number, $this->token);
        $taskRepo->updateCaseStartedTaskStatus();

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->instance->case_number,
            'tasks->[0]->id' => $this->token->id,
            'tasks->[0]->element_id' => $this->token->element_id,
            'tasks->[0]->name' => $this->token->element_name,
            'tasks->[0]->process_id' => $this->token->process_id,
            'tasks->[0]->status' => $this->token->status,
        ]);
    }

    public function test_update_case_participated_task_status()
    {
        $repo = new CaseRepository();
        $repo->create($this->instance);
        $repo->update($this->instance, $this->token);

        $this->assertDatabaseCount('cases_participated', 1);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->instance->case_number,
            'tasks->[0]->id' => $this->token->id,
            'tasks->[0]->element_id' => $this->token->element_id,
            'tasks->[0]->name' => $this->token->element_name,
            'tasks->[0]->process_id' => $this->token->process_id,
            'tasks->[0]->status' => $this->token->status,
        ]);

        $this->token->status = 'COMPLETED';

        $taskRepo = new CaseTaskRepository($this->instance->case_number, $this->token);
        $taskRepo->updateCaseParticipatedTaskStatus();

        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->instance->case_number,
            'tasks->[0]->id' => $this->token->id,
            'tasks->[0]->element_id' => $this->token->element_id,
            'tasks->[0]->name' => $this->token->element_name,
            'tasks->[0]->process_id' => $this->token->process_id,
            'tasks->[0]->status' => $this->token->status,
        ]);
    }

    public function test_update_case_started_task_status_exception()
    {
        $repo = new CaseRepository();
        $repo->create($this->instance);
        $repo->update($this->instance, $this->token);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->instance->case_number,
            'tasks->[0]->id' => $this->token->id,
            'tasks->[0]->element_id' => $this->token->element_id,
            'tasks->[0]->name' => $this->token->element_name,
            'tasks->[0]->process_id' => $this->token->process_id,
            'tasks->[0]->status' => $this->token->status,
        ]);

        $this->token->status = 'COMPLETED';
        $taskRepo = new CaseTaskRepository(9999, $this->token);
        $taskRepo->updateCaseStartedTaskStatus();

        \Log::shouldReceive('error')
            ->with('CaseException: ' . 'Case not found, case_number=9999, task_id=' . $this->token->id);

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->instance->case_number,
            'tasks->[0]->status' => 'ACTIVE',
        ]);
    }

    public function test_update_case_started_script_task_status_do_not_show_error()
    {
        // Mock the Log facade to check if no error is logged
        $mock = Mockery::mock(Logger::class);
        $mock->shouldReceive('error')->never();
        Log::swap($mock);

        // Change the element type to script
        $this->token->element_type = 'scriptTask';
        $this->token->save();

        $repo = new CaseRepository();
        $repo->create($this->instance);
        $repo->update($this->instance, $this->token);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->instance->case_number,
            'request_tokens->[0]' => $this->token->id,
        ]);

        $this->token->status = 'COMPLETED';
        $taskRepo = new CaseTaskRepository(9999, $this->token);
        $taskRepo->updateCaseStartedTaskStatus();
    }

    public function test_find_case_by_task_id_case_found()
    {
        $taskRepo = new CaseTaskRepository($this->caseNumber, $this->task);

        CaseStartedFactory::new()->create([
            'case_number' => $this->caseNumber,
            'tasks' => [
                ['id' => 'task-1', 'status' => 'ACTIVE'],
                ['id' => 'task-2', 'status' => 'ACTIVE']
            ],
        ]);

        $taskRepo->setTable('cases_started');
        $result = $taskRepo->findCaseByTaskId($this->caseNumber, 'task-1');

        $this->assertNotNull($result);
        $this->assertEquals($this->caseNumber, $result->case_number);
        $this->assertEquals('$[0].id', $result->task_index);
    }

    public function test_find_case_by_task_id_case_not_found()
    {
        $taskRepo = new CaseTaskRepository($this->caseNumber, $this->task);
        $taskRepo->setTable('cases_started');
        $result = $taskRepo->findCaseByTaskId($this->caseNumber, 'task-999');

        $this->assertNull($result);
    }

    public function test_update_task_status_in_case()
    {
        CaseStartedFactory::new()->create([
            'case_number' => $this->caseNumber,
            'tasks' => [
                ['id' => 'task-1', 'status' => 'ACTIVE'],
                ['id' => 'task-2', 'status' => 'ACTIVE']
            ],
        ]);

        $taskRepo = new CaseTaskRepository($this->caseNumber, $this->task);
        $taskRepo->setTable('cases_started');
        $taskRepo->updateTaskStatusInCase($this->caseNumber, '$[0]', 'COMPLETED');

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->caseNumber,
            'tasks->[0]->id' => 'task-1',
            'tasks->[0]->status' => 'COMPLETED',
            'tasks->[1]->id' => 'task-2',
            'tasks->[1]->status' => 'ACTIVE',
        ]);
    }

    public function test_update_task_status_in_case_not_found()
    {
        CaseStartedFactory::new()->create([
            'case_number' => $this->caseNumber,
            'tasks' => [
                ['id' => 'task-1', 'status' => 'ACTIVE'],
                ['id' => 'task-2', 'status' => 'ACTIVE']
            ],
        ]);

        $taskRepo = new CaseTaskRepository($this->caseNumber, $this->task);
        $taskRepo->setTable('any');
        $taskRepo->updateTaskStatus($this->caseNumber, 1000, null);

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->caseNumber,
            'tasks->[0]->id' => 'task-1',
            'tasks->[0]->status' => 'ACTIVE',
            'tasks->[1]->id' => 'task-2',
            'tasks->[1]->status' => 'ACTIVE',
        ]);
    }
}

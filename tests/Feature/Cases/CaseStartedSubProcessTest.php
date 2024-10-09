<?php

namespace Tests\Feature\Cases;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseRepository;
use Tests\TestCase;

class CaseStartedSubProcessTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected $user2;

    protected $process;

    protected $parentRequest;

    protected $subProcess;

    protected $childRequest;

    protected $parentToken;

    protected $childToken;

    protected $childToken2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->process = Process::factory()->create();
        $this->parentRequest = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
            'process_id' => $this->process->id,
        ]);
        $this->parentToken = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->parentRequest->id,
            'element_type' => 'task',
        ]);
        $this->subProcess = Process::factory()->create();
        $this->childRequest = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
            'parent_request_id' => $this->parentRequest->id,
            'process_id' => $this->subProcess->id,
        ]);
        $this->childToken = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->childRequest->id,
            'element_type' => 'task',
        ]);

        $this->user2 = User::factory()->create();
        $this->childToken2 = ProcessRequestToken::factory()->create([
            'user_id' => $this->user2->id,
            'process_request_id' => $this->childRequest->id,
            'element_type' => 'task',
        ]);
    }

    public function test_create_case_sub_process()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);

        $repo = new CaseRepository();
        $repo->create($this->childRequest);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
        ]);
    }

    public function test_create_case_processes()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);

        $repo = new CaseRepository();
        $repo->create($this->childRequest);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'processes->[1]->id' => $this->subProcess->id,
            'processes->[1]->name' => $this->subProcess->name,
        ]);
    }

    public function test_create_case_requests()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);

        $repo = new CaseRepository();
        $repo->create($this->childRequest);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'requests->[0]->id' => $this->parentRequest->id,
            'requests->[0]->name' => $this->parentRequest->name,
            'requests->[0]->parent_request_id' => $this->parentRequest->parent_request_id,
            'requests->[1]->id' => $this->childRequest->id,
            'requests->[1]->name' => $this->childRequest->name,
            'requests->[1]->parent_request_id' => $this->childRequest->parent_request_id,
        ]);
    }

    public function test_create_case_request_tokens()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);

        $repo = new CaseRepository();
        $repo->create($this->childRequest);
        $repo->update($this->parentRequest, $this->parentToken);
        $repo->update($this->childRequest, $this->childToken);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $this->parentToken->id,
            'request_tokens->[1]' => $this->childToken->id,
        ]);
    }

    public function test_create_case_tasks()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);

        $repo = new CaseRepository();
        $repo->create($this->childRequest);
        $repo->update($this->parentRequest, $this->parentToken);
        $repo->update($this->childRequest, $this->childToken);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'tasks->[0]->id' => $this->parentToken->id,
            'tasks->[0]->element_id' => $this->parentToken->element_id,
            'tasks->[0]->name' => $this->parentToken->element_name,
            'tasks->[0]->process_id' => $this->parentToken->process_id,
            'tasks->[1]->id' => $this->childToken->id,
            'tasks->[1]->element_id' => $this->childToken->element_id,
            'tasks->[1]->name' => $this->childToken->element_name,
            'tasks->[1]->process_id' => $this->childToken->process_id,
        ]);
    }

    public function test_create_case_participated_processes()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);
        $repo->create($this->childRequest);

        $this->assertDatabaseCount('cases_participated', 0);

        $repo->update($this->parentRequest, $this->parentToken);
        $repo->update($this->childRequest, $this->childToken);
        $repo->update($this->childRequest, $this->childToken2);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'processes->[1]->id' => $this->subProcess->id,
            'processes->[1]->name' => $this->subProcess->name,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user2->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $this->subProcess->id,
            'processes->[0]->name' => $this->subProcess->name,
        ]);
    }

    public function test_create_case_participated_requests()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);
        $repo->create($this->childRequest);

        $this->assertDatabaseCount('cases_participated', 0);

        $repo->update($this->parentRequest, $this->parentToken);
        $repo->update($this->childRequest, $this->childToken);
        $repo->update($this->childRequest, $this->childToken2);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'requests->[0]->id' => $this->parentRequest->id,
            'requests->[0]->name' => $this->parentRequest->name,
            'requests->[0]->parent_request_id' => $this->parentRequest->parent_request_id,
            'requests->[1]->id' => $this->childRequest->id,
            'requests->[1]->name' => $this->childRequest->name,
            'requests->[1]->parent_request_id' => $this->childRequest->parent_request_id,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user2->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'requests->[0]->id' => $this->childRequest->id,
            'requests->[0]->name' => $this->childRequest->name,
            'requests->[0]->parent_request_id' => $this->childRequest->parent_request_id,
        ]);
    }

    public function test_create_case_participated_request_tokens()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);
        $repo->create($this->childRequest);

        $this->assertDatabaseCount('cases_participated', 0);

        $repo->update($this->parentRequest, $this->parentToken);
        $repo->update($this->childRequest, $this->childToken);
        $repo->update($this->childRequest, $this->childToken2);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $this->parentToken->id,
            'request_tokens->[1]' => $this->childToken->id,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user2->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $this->childToken2->id,
        ]);
    }

    public function test_create_case_participated_tasks()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);
        $repo->create($this->childRequest);

        $this->assertDatabaseCount('cases_participated', 0);

        $repo->update($this->parentRequest, $this->parentToken);
        $repo->update($this->childRequest, $this->childToken);
        $repo->update($this->childRequest, $this->childToken2);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'tasks->[0]->id' => $this->parentToken->id,
            'tasks->[0]->element_id' => $this->parentToken->element_id,
            'tasks->[0]->name' => $this->parentToken->element_name,
            'tasks->[0]->process_id' => $this->parentToken->process_id,
            'tasks->[1]->id' => $this->childToken->id,
            'tasks->[1]->element_id' => $this->childToken->element_id,
            'tasks->[1]->name' => $this->childToken->element_name,
            'tasks->[1]->process_id' => $this->childToken->process_id,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user2->id,
            'case_title' => 'Case #' . $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'tasks->[0]->id' => $this->childToken2->id,
            'tasks->[0]->element_id' => $this->childToken2->element_id,
            'tasks->[0]->name' => $this->childToken2->element_name,
            'tasks->[0]->process_id' => $this->childToken2->process_id,
            'tasks->[1]->id' => null,
            'tasks->[1]->element_id' => null,
            'tasks->[1]->name' => null,
            'tasks->[1]->process_id' => null,
        ]);
    }

    public function test_update_case_participated_completed()
    {
        $repo = new CaseRepository();
        $repo->create($this->parentRequest);
        $repo->create($this->childRequest);

        $repo->update($this->parentRequest, $this->parentToken);
        $repo->update($this->childRequest, $this->childToken);
        $repo->update($this->childRequest, $this->childToken2);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseCount('cases_participated', 2);

        $this->childRequest->status = 'COMPLETED';
        $repo->updateStatus($this->childRequest);

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->parentRequest->case_number,
            'case_status' => 'IN_PROGRESS',
            'completed_at' => null,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_status' => 'IN_PROGRESS',
            'completed_at' => null,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user2->id,
            'case_status' => 'IN_PROGRESS',
            'completed_at' => null,
        ]);

        $this->parentRequest->status = 'COMPLETED';
        $repo->updateStatus($this->parentRequest);

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->parentRequest->case_number,
            'case_status' => 'COMPLETED',
            'completed_at' => now(),
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user->id,
            'case_status' => 'COMPLETED',
            'completed_at' => now(),
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $this->parentRequest->case_number,
            'user_id' => $this->user2->id,
            'case_status' => 'COMPLETED',
            'completed_at' => now(),
        ]);
    }
}

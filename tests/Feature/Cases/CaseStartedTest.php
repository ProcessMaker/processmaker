<?php

namespace Tests\Feature\Cases;

use Database\Factories\ProcessMaker\Models\ProcessCategoryFactory;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseRepository;
use Tests\TestCase;

class CaseStartedTest extends TestCase
{
    public function test_create_case()
    {
        $user = User::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
        ]);
    }

    public function test_create_multiple_cases()
    {
        $user = User::factory()->create();
        $instance1 = ProcessRequest::factory()->create([
            'user_id' => $user->id,
        ]);
        $instance2 = ProcessRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance1);
        $repo->create($instance2);

        $this->assertDatabaseCount('cases_started', 2);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance1->case_number,
            'user_id' => $user->id,
            'case_title' => $instance1->case_title,
            'case_status' => 'IN_PROGRESS',
        ]);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance2->case_number,
            'user_id' => $user->id,
            'case_title' => $instance2->case_title,
            'case_status' => 'IN_PROGRESS',
        ]);
    }

    public function test_create_case_started_processes()
    {
        $process = Process::factory()->create();

        $user = User::factory()->create();

        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $process->id,
            'processes->[0]->name' => $process->name,
        ]);
    }

    public function test_create_case_started_requests()
    {
        $process = Process::factory()->create();

        $user = User::factory()->create();

        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'requests->[0]->id' => $instance->id,
            'requests->[0]->name' => $instance->name,
            'requests->[0]->parent_request_id' => $instance->parent_request_id ?? 0,
        ]);
    }

    public function test_update_case_started_request_tokens()
    {
        $process = Process::factory()->create();

        $user = User::factory()->create();

        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
        ]);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $token->id,
        ]);
    }

    public function test_update_case_started_tasks()
    {
        $process = Process::factory()->create();

        $user = User::factory()->create();

        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
        ]);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
            'tasks->[0]->status' => $token->status,
        ]);

        $token2 = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token2);

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'tasks->[0]->id' => $token2->id,
            'tasks->[0]->element_id' => $token2->element_id,
            'tasks->[0]->name' => $token2->element_name,
            'tasks->[0]->process_id' => $token2->process_id,
            'tasks->[0]->status' => $token2->status,
        ]);
    }

    public function test_update_case_started_script_tasks()
    {
        $process = Process::factory()->create();

        $user = User::factory()->create();

        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
        ]);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
        ]);

        $token2 = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'scriptTask',
        ]);

        $repo->update($instance, $token2);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'tasks->[1]->id' => null,
            'tasks->[1]->element_id' => null,
            'tasks->[1]->name' => null,
            'tasks->[1]->process_id' => null,
        ]);
    }

    public function test_update_case_started_participants()
    {
        $process = Process::factory()->create();

        $user = User::factory()->create();

        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
        ]);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
        ]);

        $repo->update($instance, $token);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'participants->[0]' => $user->id,
        ]);

        $user2 = User::factory()->create();
        $token2 = ProcessRequestToken::factory()->create([
            'user_id' => $user2->id,
            'process_request_id' => $instance->id,
        ]);

        $repo->update($instance, $token2);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'participants->[1]' => $user2->id,
        ]);
    }

    public function test_update_case_started_status()
    {
        $process = Process::factory()->create();
        $user = User::factory()->create();

        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
        ]);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'completed_at' => null,
            'request_tokens->[0]' => $token->id,
        ]);

        $instance->status = 'COMPLETED';
        $instance->completed_at = now();
        $repo->updateStatus($instance, $token);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'COMPLETED',
            'completed_at' => $instance->completed_at,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
        ]);
    }

    public function test_update_case_started_status_to_canceled()
    {
        $process = Process::factory()->create();
        $user = User::factory()->create();

        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
        ]);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'completed_at' => null,
            'request_tokens->[0]' => $token->id,
        ]);

        $instance->status = 'CANCELED';
        $instance->completed_at = now();

        $repo->updateStatus($instance, $token);

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $instance->case_number,
            'user_id' => $user->id,
            'case_title' => $instance->case_title,
            'case_status' => 'CANCELED',
            'completed_at' => $instance->completed_at,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
        ]);
    }

    public function test_try_update_if_case_has_not_been_created()
    {
        $user = User::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => null,
        ]);

        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);
        $this->assertDatabaseCount('cases_started', 1);
    }

    public function test_try_store_docusign_authorization_request()
    {
        $category = ProcessCategoryFactory::new()->create([
            'name' => 'DocuSign',
            'status' => 'ACTIVE',
            'is_system' => true,
        ]);

        $bpmnFile = 'tests/Fixtures/process-docusign-authorization.bpmn.xml';
        $process = $this->createProcessFromBPMN($bpmnFile, [
            'process_category_id' => $category->id,
        ]);

        $startEvent = $process->getDefinitions()->getStartEvent('node_1');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, [
            'storeInEnv' => true,
        ]);

        $this->assertDatabaseCount('cases_started', 0);
        $this->assertDatabaseCount('cases_participated', 0);
    }

    public function test_try_store_error_status_on_log_error()
    {
        $user = User::factory()->create([
            'is_administrator' => true,
        ]);

        $bpmnFile = 'tests/Fixtures/process-abe.bpmn.xml';
        $process = $this->createProcessFromBPMN($bpmnFile, [
            'user_id' => $user->id,
        ]);

        $this->be($user);

        $startEvent = $process->getDefinitions()->getStartEvent('node_1');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);

        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $request->case_number,
            'case_status' => 'IN_PROGRESS',
        ]);
        $this->assertDatabaseCount('cases_participated', 1);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $request->case_number,
            'case_status' => 'IN_PROGRESS',
        ]);

        $formTask = $request->tokens()->where('element_id', 'node_2')->firstOrFail();

        // Next ABE should fail because there is no a valid IMAP configuration
        WorkflowManager::completeTask($process, $request, $formTask, ['someValue' => 123]);

        $abe = $request->tokens()->where('element_id', 'node_45')->firstOrFail();

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $request->case_number,
            'case_status' => 'ERROR',
            'tasks->[0]->id' => $formTask->id,
            'tasks->[1]->id' => null,
            'request_tokens->[0]' => $formTask->id,
            'request_tokens->[1]' => $abe->id,
        ]);

        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $request->case_number,
            'case_status' => 'ERROR',
            'tasks->[0]->id' => $formTask->id,
            'tasks->[1]->id' => null,
            'request_tokens->[0]' => $formTask->id,
            'request_tokens->[1]' => $abe->id,
        ]);
    }
}

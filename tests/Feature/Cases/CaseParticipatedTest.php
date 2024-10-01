<?php

namespace Tests\Feature\Cases;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseParticipatedRepository;
use ProcessMaker\Repositories\CaseRepository;
use Tests\TestCase;

class CaseParticipatedTest extends TestCase
{
    public function test_create_case_participated()
    {
        $user = User::factory()->create();
        $process = Process::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repoParticipant = new CaseParticipatedRepository();
        $repo = new CaseRepository($repoParticipant);
        $repo->create($instance);

        $this->assertDatabaseHas('cases_started', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
        ]);

        $this->assertDatabaseCount('cases_participated', 0);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);

        $this->assertDatabaseCount('cases_participated', 1);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $process->id,
            'processes->[0]->name' => $process->name,
            'requests->[0]->id' => $instance->id,
            'requests->[0]->name' => $instance->name,
            'requests->[0]->parent_request_id' => $instance->parent_request_id,
            'request_tokens->[0]' => $token->id,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
        ]);
    }

    public function test_create_multiple_case_participated()
    {
        $user = User::factory()->create();
        $process = Process::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repoParticipant = new CaseParticipatedRepository();
        $repo = new CaseRepository($repoParticipant);
        $repo->create($instance);

        $this->assertDatabaseHas('cases_started', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
        ]);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);

        $this->assertDatabaseCount('cases_participated', 1);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $token->id,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
        ]);

        $token2 = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token2);

        $this->assertDatabaseCount('cases_participated', 1);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $token->id,
            'request_tokens->[1]' => $token2->id,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
            'tasks->[1]->id' => $token2->id,
            'tasks->[1]->element_id' => $token2->element_id,
            'tasks->[1]->name' => $token2->element_name,
            'tasks->[1]->process_id' => $token2->process_id,
        ]);
    }

    public function test_update_case_participated_users()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $process = Process::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repoParticipant = new CaseParticipatedRepository();
        $repo = new CaseRepository($repoParticipant);
        $repo->create($instance);

        $this->assertDatabaseHas('cases_started', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
        ]);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);

        $this->assertDatabaseCount('cases_participated', 1);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $token->id,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
        ]);

        $token2 = ProcessRequestToken::factory()->create([
            'user_id' => $user2->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token2);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user2->id,
            'case_number' => $instance->case_number,
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $token2->id,
            'tasks->[0]->id' => $token2->id,
            'tasks->[0]->element_id' => $token2->element_id,
            'tasks->[0]->name' => $token2->element_name,
            'tasks->[0]->process_id' => $token2->process_id,
        ]);
    }

    public function test_update_case_participated_user_tasks()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $process = Process::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repoParticipant = new CaseParticipatedRepository();
        $repo = new CaseRepository($repoParticipant);
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);

        $this->assertDatabaseCount('cases_participated', 1);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'request_tokens->[0]' => $token->id,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
        ]);

        $token2 = ProcessRequestToken::factory()->create([
            'user_id' => $user2->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token2);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user2->id,
            'case_number' => $instance->case_number,
            'request_tokens->[0]' => $token2->id,
            'tasks->[0]->id' => $token2->id,
            'tasks->[0]->element_id' => $token2->element_id,
            'tasks->[0]->name' => $token2->element_name,
            'tasks->[0]->process_id' => $token2->process_id,
        ]);

        $token3 = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token3);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'request_tokens->[0]' => $token->id,
            'request_tokens->[1]' => $token3->id,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
            'tasks->[1]->id' => $token3->id,
            'tasks->[1]->element_id' => $token3->element_id,
            'tasks->[1]->name' => $token3->element_name,
            'tasks->[1]->process_id' => $token3->process_id,
        ]);
    }

    public function test_update_case_participated_completed()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $process = Process::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
            'process_id' => $process->id,
        ]);

        $repoParticipant = new CaseParticipatedRepository();
        $repo = new CaseRepository($repoParticipant);
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 1);

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token);

        $this->assertDatabaseCount('cases_participated', 1);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $token->id,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
        ]);

        $token2 = ProcessRequestToken::factory()->create([
            'user_id' => $user2->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token2);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user2->id,
            'case_number' => $instance->case_number,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $token2->id,
            'tasks->[0]->id' => $token2->id,
            'tasks->[0]->element_id' => $token2->element_id,
            'tasks->[0]->name' => $token2->element_name,
            'tasks->[0]->process_id' => $token2->process_id,
        ]);

        $token3 = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'process_request_id' => $instance->id,
            'element_type' => 'task',
        ]);

        $repo->update($instance, $token3);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $user->id,
            'case_number' => $instance->case_number,
            'case_status' => 'IN_PROGRESS',
            'request_tokens->[0]' => $token->id,
            'request_tokens->[1]' => $token3->id,
            'tasks->[0]->id' => $token->id,
            'tasks->[0]->element_id' => $token->element_id,
            'tasks->[0]->name' => $token->element_name,
            'tasks->[0]->process_id' => $token->process_id,
            'tasks->[1]->id' => $token3->id,
            'tasks->[1]->element_id' => $token3->element_id,
            'tasks->[1]->name' => $token3->element_name,
            'tasks->[1]->process_id' => $token3->process_id,
        ]);

        $instance->status = 'COMPLETED';
        $repo->updateStatus($instance);

        $this->assertDatabaseCount('cases_participated', 2);
        $this->assertDatabaseHas('cases_participated', [
            'case_number' => $instance->case_number,
            'case_status' => 'COMPLETED',
            'completed_at' => now(),
        ]);
    }
}

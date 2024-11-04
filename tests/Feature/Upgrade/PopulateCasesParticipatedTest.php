<?php

namespace Tests\Upgrades;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

class PopulateCasesParticipatedTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $user2;

    protected $user3;

    protected $process;

    protected $request;

    protected $subProcess1;

    protected $childRequest1;

    protected $subProcess2;

    protected $childRequest2;

    protected $token;

    protected $childToken;

    protected $childToken2;

    protected $upgrade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->user3 = User::factory()->create();

        $this->process = Process::factory()->create();
        $this->subProcess1 = Process::factory()->create();
        $this->subProcess2 = Process::factory()->create();

        $this->request = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
            'process_id' => $this->process->id,
        ]);
        $this->childRequest1 = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
            'parent_request_id' => $this->request->id,
            'process_id' => $this->subProcess1->id,
        ]);
        $this->childRequest2 = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
            'parent_request_id' => $this->request->id,
            'process_id' => $this->subProcess2->id,
        ]);
    }

    private function upgrade()
    {
        $this->artisan('migrate', [
            '--path' => 'upgrades/2024_10_09_152947_populate_cases_participated.php',
        ])->run();
    }

    public function test_one_participant()
    {
        $tokens = ProcessRequestToken::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        $this->upgrade();

        $this->assertDatabaseCount('cases_participated', 1);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user->id,
            'case_number' => $this->request->case_number,
            'case_title' => $this->request->case_title,
            'case_title_formatted' => $this->request->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'requests->[0]->id' => $this->request->id,
            'requests->[0]->name' => $this->request->name,
            'requests->[0]->parent_request_id' => $this->request->parent_request_id,
            'request_tokens->[0]' => $tokens[0]->id,
            'request_tokens->[4]' => $tokens[4]->id,
            'tasks->[0]->id' => $tokens[0]->id,
            'tasks->[0]->element_id' => $tokens[0]->element_id,
            'tasks->[0]->name' => $tokens[0]->element_name,
            'tasks->[0]->process_id' => $tokens[0]->process_id,
            'tasks->[0]->status' => $tokens[0]->status,
            'tasks->[4]->id' => $tokens[4]->id,
            'tasks->[4]->element_id' => $tokens[4]->element_id,
            'tasks->[4]->name' => $tokens[4]->element_name,
            'tasks->[4]->process_id' => $tokens[4]->process_id,
            'tasks->[4]->status' => $tokens[4]->status,
        ]);
    }

    public function test_multiple_participants()
    {
        $tokens = ProcessRequestToken::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        $tokens2 = ProcessRequestToken::factory()->count(5)->create([
            'user_id' => $this->user2->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        $tokens3 = ProcessRequestToken::factory()->count(5)->create([
            'user_id' => $this->user3->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        $this->upgrade();

        $this->assertDatabaseCount('cases_participated', 3);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user->id,
            'case_number' => $this->request->case_number,
            'case_title' => $this->request->case_title,
            'case_title_formatted' => $this->request->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'requests->[0]->id' => $this->request->id,
            'requests->[0]->name' => $this->request->name,
            'requests->[0]->parent_request_id' => $this->request->parent_request_id,
            'request_tokens' => 1,
            'request_tokens->[0]' => $tokens[0]->id,
            'request_tokens->[9]' => $tokens2[4]->id,
            'request_tokens->[14]' => $tokens3[4]->id,
            'tasks->[0]->id' => $tokens[0]->id,
            'tasks->[0]->element_id' => $tokens[0]->element_id,
            'tasks->[0]->name' => $tokens[0]->element_name,
            'tasks->[0]->process_id' => $tokens[0]->process_id,
            'tasks->[0]->status' => $tokens[0]->status,
            'tasks->[9]->id' => $tokens2[4]->id,
            'tasks->[9]->element_id' => $tokens2[4]->element_id,
            'tasks->[9]->name' => $tokens2[4]->element_name,
            'tasks->[9]->process_id' => $tokens2[4]->process_id,
            'tasks->[9]->status' => $tokens2[4]->status,
            'tasks->[14]->id' => $tokens3[4]->id,
            'tasks->[14]->element_id' => $tokens3[4]->element_id,
            'tasks->[14]->name' => $tokens3[4]->element_name,
            'tasks->[14]->process_id' => $tokens3[4]->process_id,
            'tasks->[14]->status' => $tokens3[4]->status,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user2->id,
            'case_number' => $this->request->case_number,
            'case_title' => $this->request->case_title,
            'case_title_formatted' => $this->request->case_title_formatted,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'requests->[0]->id' => $this->request->id,
            'requests->[0]->name' => $this->request->name,
            'requests->[0]->parent_request_id' => $this->request->parent_request_id,
            'request_tokens' => 1,
            'request_tokens->[0]' => $tokens[0]->id,
            'request_tokens->[9]' => $tokens2[4]->id,
            'request_tokens->[14]' => $tokens3[4]->id,
            'tasks->[0]->id' => $tokens[0]->id,
            'tasks->[0]->element_id' => $tokens[0]->element_id,
            'tasks->[0]->name' => $tokens[0]->element_name,
            'tasks->[0]->process_id' => $tokens[0]->process_id,
            'tasks->[0]->status' => $tokens[0]->status,
            'tasks->[9]->id' => $tokens2[4]->id,
            'tasks->[9]->element_id' => $tokens2[4]->element_id,
            'tasks->[9]->name' => $tokens2[4]->element_name,
            'tasks->[9]->process_id' => $tokens2[4]->process_id,
            'tasks->[9]->status' => $tokens2[4]->status,
            'tasks->[14]->id' => $tokens3[4]->id,
            'tasks->[14]->element_id' => $tokens3[4]->element_id,
            'tasks->[14]->name' => $tokens3[4]->element_name,
            'tasks->[14]->process_id' => $tokens3[4]->process_id,
            'tasks->[14]->status' => $tokens3[4]->status,
        ]);
    }

    public function test_participants()
    {
        ProcessRequestToken::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        ProcessRequestToken::factory()->count(5)->create([
            'user_id' => $this->user2->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        ProcessRequestToken::factory()->count(5)->create([
            'user_id' => $this->user3->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        $this->upgrade();

        $this->assertDatabaseCount('cases_participated', 3);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user->id,
            'case_number' => $this->request->case_number,
            'participants->[0]' => $this->user->id,
            'participants->[1]' => $this->user2->id,
            'participants->[2]' => $this->user3->id,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user2->id,
            'case_number' => $this->request->case_number,
            'participants->[0]' => $this->user->id,
            'participants->[1]' => $this->user2->id,
            'participants->[2]' => $this->user3->id,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user3->id,
            'case_number' => $this->request->case_number,
            'participants->[0]' => $this->user->id,
            'participants->[1]' => $this->user2->id,
            'participants->[2]' => $this->user3->id,
        ]);
    }

    public function test_request_tokens()
    {
        $tokens = ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        $tokens2 = ProcessRequestToken::factory()->count(3)->create([
            'user_id' => $this->user2->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        $tokens3 = ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user3->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
        ]);

        $this->upgrade();

        $this->assertDatabaseCount('cases_participated', 3);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user->id,
            'case_number' => $this->request->case_number,
            'request_tokens->[0]' => $tokens[0]->id,
            'request_tokens->[1]' => $tokens[1]->id,
            'request_tokens->[2]' => $tokens2[0]->id,
            'request_tokens->[3]' => $tokens2[1]->id,
            'request_tokens->[4]' => $tokens2[2]->id,
            'request_tokens->[5]' => $tokens3[0]->id,
            'request_tokens->[6]' => $tokens3[1]->id,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user2->id,
            'case_number' => $this->request->case_number,
            'request_tokens->[0]' => $tokens[0]->id,
            'request_tokens->[1]' => $tokens[1]->id,
            'request_tokens->[2]' => $tokens2[0]->id,
            'request_tokens->[3]' => $tokens2[1]->id,
            'request_tokens->[4]' => $tokens2[2]->id,
            'request_tokens->[5]' => $tokens3[0]->id,
            'request_tokens->[6]' => $tokens3[1]->id,
        ]);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user3->id,
            'case_number' => $this->request->case_number,
            'request_tokens->[0]' => $tokens[0]->id,
            'request_tokens->[1]' => $tokens[1]->id,
            'request_tokens->[2]' => $tokens2[0]->id,
            'request_tokens->[3]' => $tokens2[1]->id,
            'request_tokens->[4]' => $tokens2[2]->id,
            'request_tokens->[5]' => $tokens3[0]->id,
            'request_tokens->[6]' => $tokens3[1]->id,
        ]);
    }

    public function test_tasks()
    {
        $tokens = ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);

        $tokens2 = ProcessRequestToken::factory()->count(3)->create([
            'user_id' => $this->user2->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
            'status' => 'COMPLETED',
        ]);

        $tokens3 = ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user3->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);

        $this->upgrade();

        $this->assertDatabaseCount('cases_participated', 3);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user->id,
            'case_number' => $this->request->case_number,
            'tasks->[0]->id' => $tokens[0]->id,
            'tasks->[0]->status' => $tokens[0]->status,
            'tasks->[1]->id' => $tokens[1]->id,
            'tasks->[1]->status' => $tokens[1]->status,
            'tasks->[2]->id' => $tokens2[0]->id,
            'tasks->[2]->status' => $tokens2[0]->status,
            'tasks->[3]->id' => $tokens2[1]->id,
            'tasks->[3]->status' => $tokens2[1]->status,
            'tasks->[4]->id' => $tokens2[2]->id,
            'tasks->[4]->status' => $tokens2[2]->status,
            'tasks->[5]->id' => $tokens3[0]->id,
            'tasks->[5]->status' => $tokens3[0]->status,
            'tasks->[6]->id' => $tokens3[1]->id,
            'tasks->[6]->status' => $tokens3[1]->status,
        ]);
    }

    public function test_sub_processes()
    {
        ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);

        ProcessRequestToken::factory()->count(3)->create([
            'user_id' => $this->user2->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
            'status' => 'COMPLETED',
        ]);

        ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user3->id,
            'process_request_id' => $this->childRequest1->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);

        $this->upgrade();

        $this->assertDatabaseCount('cases_participated', 3);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user->id,
            'case_number' => $this->request->case_number,
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'processes->[1]->id' => $this->subProcess1->id,
            'processes->[1]->name' => $this->subProcess1->name,
        ]);
    }

    public function test_child_requests()
    {
        ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);

        ProcessRequestToken::factory()->count(3)->create([
            'user_id' => $this->user2->id,
            'process_request_id' => $this->childRequest1->id,
            'element_type' => 'task',
            'status' => 'COMPLETED',
        ]);

        ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user3->id,
            'process_request_id' => $this->childRequest2->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);

        $this->upgrade();

        $this->assertDatabaseCount('cases_participated', 3);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user->id,
            'case_number' => $this->request->case_number,
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'processes->[1]->id' => $this->subProcess1->id,
            'processes->[1]->name' => $this->subProcess1->name,
            'processes->[2]->id' => $this->subProcess2->id,
            'processes->[2]->name' => $this->subProcess2->name,
            'requests->[0]->id' => $this->request->id,
            'requests->[0]->name' => $this->request->name,
            'requests->[0]->parent_request_id' => $this->request->parent_request_id,
            'requests->[1]->id' => $this->childRequest1->id,
            'requests->[1]->name' => $this->childRequest1->name,
            'requests->[1]->parent_request_id' => $this->childRequest1->parent_request_id,
            'requests->[2]->id' => $this->childRequest2->id,
            'requests->[2]->name' => $this->childRequest2->name,
            'requests->[2]->parent_request_id' => $this->childRequest2->parent_request_id,
        ]);
    }

    public function test_sub_process_tasks()
    {
        $tokens = ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->request->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);

        $tokens2 = ProcessRequestToken::factory()->count(3)->create([
            'user_id' => $this->user2->id,
            'process_request_id' => $this->childRequest1->id,
            'element_type' => 'task',
            'status' => 'COMPLETED',
        ]);

        $tokens3 = ProcessRequestToken::factory()->count(2)->create([
            'user_id' => $this->user3->id,
            'process_request_id' => $this->childRequest2->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);

        $this->upgrade();

        $this->assertDatabaseCount('cases_participated', 3);
        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user->id,
            'case_number' => $this->request->case_number,
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'processes->[1]->id' => $this->subProcess1->id,
            'processes->[1]->name' => $this->subProcess1->name,
            'processes->[2]->id' => $this->subProcess2->id,
            'processes->[2]->name' => $this->subProcess2->name,
            'requests->[0]->id' => $this->request->id,
            'requests->[0]->name' => $this->request->name,
            'requests->[0]->parent_request_id' => $this->request->parent_request_id,
            'requests->[1]->id' => $this->childRequest1->id,
            'requests->[1]->name' => $this->childRequest1->name,
            'requests->[1]->parent_request_id' => $this->childRequest1->parent_request_id,
            'requests->[2]->id' => $this->childRequest2->id,
            'requests->[2]->name' => $this->childRequest2->name,
            'requests->[2]->parent_request_id' => $this->childRequest2->parent_request_id,
        ]);

        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user->id,
            'case_number' => $this->request->case_number,
            'tasks->[0]->id' => $tokens[0]->id,
            'tasks->[1]->id' => $tokens[1]->id,
            'tasks->[2]->id' => $tokens2[0]->id,
            'tasks->[3]->id' => $tokens2[1]->id,
            'tasks->[4]->id' => $tokens2[2]->id,
            'tasks->[5]->id' => $tokens3[0]->id,
            'tasks->[6]->id' => $tokens3[1]->id,
        ]);

        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user2->id,
            'case_number' => $this->request->case_number,
            'tasks->[0]->id' => $tokens[0]->id,
            'tasks->[1]->id' => $tokens[1]->id,
            'tasks->[2]->id' => $tokens2[0]->id,
            'tasks->[3]->id' => $tokens2[1]->id,
            'tasks->[4]->id' => $tokens2[2]->id,
            'tasks->[5]->id' => $tokens3[0]->id,
            'tasks->[6]->id' => $tokens3[1]->id,
        ]);

        $this->assertDatabaseHas('cases_participated', [
            'user_id' => $this->user3->id,
            'case_number' => $this->request->case_number,
            'tasks->[0]->id' => $tokens[0]->id,
            'tasks->[1]->id' => $tokens[1]->id,
            'tasks->[2]->id' => $tokens2[0]->id,
            'tasks->[3]->id' => $tokens2[1]->id,
            'tasks->[4]->id' => $tokens2[2]->id,
            'tasks->[5]->id' => $tokens3[0]->id,
            'tasks->[6]->id' => $tokens3[1]->id,
        ]);
    }
}

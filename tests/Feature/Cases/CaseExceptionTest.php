<?php

namespace Tests\Feature\Cases;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseRepository;
use Tests\TestCase;

class CaseExceptionTest extends TestCase
{
    protected $user;

    protected $process;

    protected $instance;

    protected $instance2;

    protected $token;

    protected $token2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->process = Process::factory()->create();

        $this->instance = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $this->process->id,
        ]);
        $this->token = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->instance->id,
            'element_type' => 'task',
        ]);

        $this->instance2 = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $this->process->id,
        ]);
        $this->token2 = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->instance2->id,
            'element_type' => 'task',
        ]);
    }

    public function test_create_case_missing_case_number(): void
    {
        $this->withoutExceptionHandling();

        $this->instance->case_number = null;
        $repo = new CaseRepository();
        $repo->create($this->instance);

        $this->assertDatabaseCount('cases_started', 0);
    }

    public function test_create_case_with_null_user_id(): void
    {
        // Disable exception handling for better test debugging
        $this->withoutExceptionHandling();

        // Set the user_id to null to simulate a scenario where the user ID is not provided.
        // This is a valid case for processes like the Expense Report, where user_id can be null.
        $this->instance->user_id = null;

        // Instantiate the repository responsible for handling case creation.
        $repo = new CaseRepository();

        // Attempt to create the case with a null user_id and ensure that the application
        // allows this without throwing any exceptions or errors.
        $repo->create($this->instance);

        // Verify that a record was successfully inserted into the 'cases_started' table
        // with 'user_id' set to null, which validates that null values are supported.
        $this->assertDatabaseHas('cases_started', [
            'user_id' => null,  // Ensure that user_id is null in the created case
            // Additional assertions could include fields like 'case_title' or 'case_number'
            // if they are unique for further validation
        ]);

        // Assert that exactly one record exists in the 'cases_started' table after this operation.
        // This ensures the test is isolated and no leftover data from previous tests exists.
        $this->assertDatabaseCount('cases_started', 1);
    }

    public function test_create_case_missing_case_title(): void
    {
        $this->withoutExceptionHandling();

        try {
            $this->instance->case_title = null;
            $repo = new CaseRepository();
            $repo->create($this->instance);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Column \'case_title\' cannot be null', $e->getMessage());
        }

        $this->assertDatabaseCount('cases_started', 0);
    }

    public function test_update_case_missing_case_started(): void
    {
        $this->withoutExceptionHandling();

        try {
            $this->instance->case_title = null;
            $repo = new CaseRepository();
            $repo->create($this->instance);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Column \'case_title\' cannot be null', $e->getMessage());
        }

        $this->assertDatabaseCount('cases_started', 0);

        $repo->update($this->instance, $this->token);
    }

    public function test_update_case_system_process(): void
    {
        $this->withoutExceptionHandling();
        $process = Process::factory()->create([
            'process_category_id' => ProcessCategory::factory()->create(['is_system' => true])->id,
        ]);
        $instance = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $process->id,
        ]);

        $instance->case_title = null;
        $repo = new CaseRepository();
        $repo->create($instance);

        $this->assertDatabaseCount('cases_started', 0);

        Log::shouldReceive('error')->never()->withAnyArgs();
        $repo->update($instance, $this->token);

        Log::shouldReceive('error')->never()->withAnyArgs();
        $repo->updateStatus($instance, $this->token);
    }

    public function test_artisan_sync_command_missing_ids(): void
    {
        $this->artisan('cases:sync')
            ->expectsOutput('Please specify a list of request IDs.')
            ->assertExitCode(0);
    }

    public function test_artisan_sync_command_success(): void
    {
        $this->artisan('cases:sync --request_ids=' . $this->instance->id . ',' . $this->instance2->id)
            ->expectsOutput('Case started synced ' . $this->instance->case_number)
            ->expectsOutput('Case started synced ' . $this->instance2->case_number)
            ->assertExitCode(0);

        $this->assertDatabaseCount('cases_started', 2);
        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->instance->case_number,
            'user_id' => $this->user->id,
            'case_title' => $this->instance->case_title,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'requests->[0]->id' => $this->instance->id,
            'requests->[0]->name' => $this->instance->name,
            'requests->[0]->parent_request_id' => $this->instance->parent_request_id ?? 0,
            'request_tokens->[0]' => $this->token->id,
            'tasks->[0]->id' => $this->token->id,
            'tasks->[0]->element_id' => $this->token->element_id,
            'tasks->[0]->name' => $this->token->element_name,
            'tasks->[0]->process_id' => $this->token->process_id,
            'participants->[0]' => $this->user->id,
        ]);

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $this->instance2->case_number,
            'user_id' => $this->user->id,
            'case_title' => $this->instance2->case_title,
            'case_status' => 'IN_PROGRESS',
            'processes->[0]->id' => $this->process->id,
            'processes->[0]->name' => $this->process->name,
            'requests->[0]->id' => $this->instance2->id,
            'requests->[0]->name' => $this->instance2->name,
            'requests->[0]->parent_request_id' => $this->instance2->parent_request_id ?? 0,
            'request_tokens->[0]' => $this->token2->id,
            'tasks->[0]->id' => $this->token2->id,
            'tasks->[0]->element_id' => $this->token2->element_id,
            'tasks->[0]->name' => $this->token2->element_name,
            'tasks->[0]->process_id' => $this->token2->process_id,
            'participants->[0]' => $this->user->id,
        ]);
    }
}

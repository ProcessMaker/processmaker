<?php

namespace Tests\Feature\Cases;

use ProcessMaker\Models\Process;
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

    public function test_create_case_missing_user_id(): void
    {
        $this->withoutExceptionHandling();

        try {
            $this->instance->user_id = null;
            $repo = new CaseRepository();
            $repo->create($this->instance);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Column \'user_id\' cannot be null', $e->getMessage());
        }

        $this->assertDatabaseCount('cases_started', 0);
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

        try {
            $repo->update($this->instance, $this->token);
        } catch (\Exception $e) {
            $this->assertEquals(
                'case started not found, method=update, instance=' . $this->instance->getKey(), $e->getMessage()
            );
        }
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
    }
}

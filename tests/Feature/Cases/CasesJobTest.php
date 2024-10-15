<?php

namespace Tests\Feature\Jobs;

use Illuminate\Support\Facades\Queue;
use ProcessMaker\Jobs\CaseStore;
use ProcessMaker\Jobs\CaseUpdate;
use ProcessMaker\Jobs\CaseUpdateStatus;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CasesJobTest extends TestCase
{
    use RequestHelper;

    public function test_handle_case_store_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        CaseStore::dispatch($instance);

        Queue::assertPushed(CaseStore::class, 1);
    }

    public function test_handle_case_update_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $instance->id,
        ]);

        CaseUpdate::dispatch($instance, $token);

        Queue::assertPushed(CaseUpdate::class, 1);
    }

    public function test_handle_case_update_status_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $instance->id,
        ]);

        CaseUpdateStatus::dispatch($instance, $token);

        Queue::assertPushed(CaseUpdateStatus::class, 1);
    }
}

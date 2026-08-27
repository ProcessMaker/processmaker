<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use ProcessMaker\Jobs\CaseUpdate;
use ProcessMaker\Jobs\ProcessCaseBatch;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseRepository;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CaseUpdateBatchTest extends TestCase
{
    use RequestHelper;

    public static function createCasesStartedForUser(int $userId, int $count = 1, array $data = [])
    {
        return CaseStarted::factory()->count($count)->create(array_merge(['user_id' => $userId], $data));
    }

    public function test_case_update_uses_direct_update_when_batching_disabled()
    {
        config(['queue-optimization.batching.case_updates.enabled' => false]);
        Queue::fake();
        Cache::flush();

        $user = $this->user;
        [$case, $instance] = $this->createCaseWithRequest($user, [
            'request_tokens' => [],
            'tasks' => [],
            'participants' => [],
        ]);
        $token = $this->createToken($instance, $user, 1, 'Stage 1');

        (new CaseUpdate($instance, $token))->handle(app(CaseRepository::class));

        Queue::assertNotPushed(ProcessCaseBatch::class);
        $this->assertNull(Cache::get("case_update_batch_{$instance->case_number}"));

        $case->refresh();
        $this->assertContains($token->id, $case->request_tokens->all());
        $this->assertContains($user->id, $case->participants->all());
    }

    public function test_case_update_batching_system()
    {
        config(['queue-optimization.batching.case_updates.enabled' => true]);
        Queue::fake();
        Cache::flush();

        $user = $this->user;
        [$case, $instance] = $this->createCaseWithRequest($user);
        $token1 = $this->createToken($instance, $user, 1, 'Stage 1');
        $token2 = $this->createToken($instance, $user, 2, 'Stage 2');

        $repository = app(CaseRepository::class);
        (new CaseUpdate($instance, $token1))->handle($repository);
        (new CaseUpdate($instance, $token2))->handle($repository);

        Queue::assertPushedOn('bpmn-batch', ProcessCaseBatch::class);
        Queue::assertPushed(ProcessCaseBatch::class, 1);
        Queue::assertPushed(ProcessCaseBatch::class, function (ProcessCaseBatch $job) use ($instance) {
            return $job->getCaseNumber() === (int) $instance->case_number;
        });

        $batch = Cache::get("case_update_batch_{$instance->case_number}");
        $this->assertCount(2, $batch);
        $this->assertEquals($token1->id, $batch[0]['token_id']);
        $this->assertEquals($token2->id, $batch[1]['token_id']);
        $this->assertNotNull($case->fresh());
    }

    public function test_case_update_immediate_for_critical_updates()
    {
        config(['queue-optimization.batching.case_updates.enabled' => true]);
        Queue::fake();
        Cache::flush();

        $user = $this->user;
        [$case, $instance] = $this->createCaseWithRequest($user, [
            'request_tokens' => [],
            'tasks' => [],
            'participants' => [],
        ]);
        $token = $this->createToken($instance, $user, 1, 'Stage 1');

        (new CaseUpdate($instance, $token, false))->handle(app(CaseRepository::class));

        Queue::assertNotPushed(ProcessCaseBatch::class);
        $this->assertNull(Cache::get("case_update_batch_{$instance->case_number}"));

        $case->refresh();
        $this->assertContains($token->id, $case->request_tokens->all());
        $this->assertContains($user->id, $case->participants->all());
    }

    public function test_batch_processing_with_multiple_tokens()
    {
        Cache::flush();

        $user = $this->user;
        [$case, $instance] = $this->createCaseWithRequest($user, [
            'request_tokens' => [],
            'tasks' => [],
            'participants' => [],
        ]);

        $batchData = [];
        for ($i = 1; $i <= 5; $i++) {
            $token = $this->createToken($instance, $user, $i, "Stage {$i}");
            $batchData[] = [
                'instance_id' => $instance->getKey(),
                'token_id' => $token->getKey(),
                'timestamp' => Carbon::now()->addSeconds($i)->timestamp,
            ];
        }

        Cache::put("case_update_batch_{$instance->case_number}", $batchData, 5);

        (new ProcessCaseBatch((int) $instance->case_number))
            ->handle(app(CaseRepository::class));

        $case->refresh();
        $this->assertCount(5, $case->request_tokens);
        $this->assertContains($user->id, $case->participants->all());
        $this->assertEquals(5, $case->last_stage_id);
        $this->assertEquals('Stage 5', $case->last_stage_name);
        $this->assertNull(Cache::get("case_update_batch_{$instance->case_number}"));
    }

    public function test_batch_fallback_to_individual_updates()
    {
        Cache::flush();

        $user = $this->user;
        [$case, $instance] = $this->createCaseWithRequest($user);

        Cache::put("case_update_batch_{$instance->case_number}", [[
            'instance_id' => 999999,
            'token_id' => 999999,
            'timestamp' => Carbon::now()->timestamp,
        ]], 5);

        (new ProcessCaseBatch((int) $instance->case_number))
            ->handle(app(CaseRepository::class));

        $this->assertDatabaseHas('cases_started', [
            'case_number' => $case->case_number,
            'user_id' => $user->id,
        ]);
        $this->assertNull(Cache::get("case_update_batch_{$instance->case_number}"));
    }

    private function createCaseWithRequest(User $user, array $caseData = []): array
    {
        $case = self::createCasesStartedForUser($user->id, 1, $caseData)->first();
        $instance = ProcessRequest::factory()->create([
            'user_id' => $user->id,
        ]);
        $instance->case_number = $case->case_number;
        $instance->saveQuietly();

        return [$case, $instance->fresh()];
    }

    private function createToken(ProcessRequest $instance, User $user, int $stageId, string $stageName): ProcessRequestToken
    {
        return ProcessRequestToken::factory()->create([
            'process_request_id' => $instance->id,
            'process_id' => $instance->process_id,
            'user_id' => $user->id,
            'element_type' => 'task',
            'stage_id' => $stageId,
            'stage_name' => $stageName,
        ]);
    }

}

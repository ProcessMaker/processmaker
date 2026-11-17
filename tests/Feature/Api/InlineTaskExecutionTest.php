<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Group as TestGroup;
use ProcessMaker\Contracts\ServiceTaskImplementationInterface;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\CatchSignalEventInRequest;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

#[TestGroup('process_tests')]
class InlineTaskExecutionTest extends TestCase
{
    use ProcessTestingTrait;
    use RequestHelper;

    private const FIXTURE_PATH = '/processes/inline-task-execution/';

    public function setupInlineTaskExecution(): void
    {
        $this->setUpMockScriptRunners();
        InlineTaskTestService::reset();
        InlineRevisionTestService::$duringRun = null;

        $implementations = [
            'inline-test-service-a' => InlineTaskTestService::class,
            'inline-test-service-b' => InlineTaskTestServiceB::class,
            'inline-test-service-c' => InlineTaskTestService::class,
            'inline-test-parallel-service' => InlineTaskTestService::class,
            'inline-test-collaboration-service' => InlineTaskTestService::class,
            'inline-test-call-child-service' => InlineTaskTestService::class,
            'inline-test-mi-service' => InlineTaskTestService::class,
            'inline-test-mi-setup-service' => InlineTaskTestService::class,
            'inline-test-custom-queue-service' => InlineTaskTestService::class,
            'inline-test-revision-service' => InlineRevisionTestService::class,
        ];

        foreach ($implementations as $implementation => $class) {
            $this->assertTrue(WorkflowManager::registerServiceImplementation($implementation, $class));
        }
    }

    public function teardownInlineTaskExecution(): void
    {
        InlineRevisionTestService::$duringRun = null;
        InlineTaskTestService::reset();
    }

    public function testSeq01RunsSequentialScriptAndServiceTasksInOneJob(): void
    {
        Bus::fake();
        $request = $this->startInlineProcess('InlineSequentialScriptServiceTasks.bpmn', 'start');

        $this->completeUserTask($request, 'user_task');
        $firstJob = $this->onlyJob(RunScriptTask::class, 'script_a', $request->id);
        $firstJob->handle();

        $this->assertCount(1, $this->jobs(RunScriptTask::class, requestId: $request->id));
        $this->assertCount(0, $this->jobs(RunServiceTask::class, requestId: $request->id));
        $this->assertRequestData($request, [
            'seq_script_a' => true,
            'seq_script_b' => true,
            'seq_service_c' => true,
        ]);
        $this->assertRequestCompleted($request);
        $this->assertNoRequestLocks($request);
    }

    public function testSeq02RunsDifferentServiceImplementationsInOneJob(): void
    {
        Bus::fake();
        $request = $this->startInlineProcess('InlineSequentialServiceTasks.bpmn', 'start');

        $this->onlyJob(RunServiceTask::class, 'service_a', $request->id)->handle();

        $this->assertCount(1, $this->jobs(RunServiceTask::class, requestId: $request->id));
        $this->assertSame(
            ['service_a', 'service_b', 'service_c'],
            InlineTaskTestService::elementRunsForRequest($request->id)
        );
        $this->assertRequestData($request, [
            'seq_service_a' => true,
            'seq_service_b' => true,
            'seq_service_c' => true,
        ]);
        $this->assertRequestCompleted($request);
        $this->assertNoRequestLocks($request);
    }

    public function testPar01KeepsParallelBranchTasksAsynchronous(): void
    {
        Bus::fake();
        $request = $this->startInlineProcess('InlineParallelScriptServiceTasks.bpmn', 'start');

        $this->assertCount(2, $request->tokens()->where('status', 'ACTIVE')->get());
        $scriptJob = $this->onlyJob(RunScriptTask::class, 'branch_script', $request->id);
        $serviceJob = $this->onlyJob(RunServiceTask::class, 'branch_service', $request->id);

        $scriptJob->handle();
        $this->assertSame('ACTIVE', $request->refresh()->status);
        $serviceJob->handle();

        $this->assertRequestData($request, [
            'parallel_script' => true,
            'parallel_service' => true,
        ]);
        $this->assertRequestCompleted($request);
        $this->assertNoRequestLocks($request);
    }

    public function testExc01KeepsInterruptingAndNonInterruptingBoundaryTasksAsynchronous(): void
    {
        Bus::fake();
        $interrupting = $this->startInlineProcess(
            'InlineBoundaryEventScriptTask.bpmn',
            'start_interrupting'
        );

        $this->onlyJob(RunScriptTask::class, 'interrupting_setup', $interrupting->id)->handle();
        $this->onlyJob(RunScriptTask::class, 'script_interrupting', $interrupting->id)->handle();

        $this->assertRequestData($interrupting, ['interrupting_setup' => true]);
        $this->assertRequestCompleted($interrupting);
        $this->assertNoRequestLocks($interrupting);

        Bus::fake();
        $nonInterrupting = $this->startInlineProcess(
            'InlineBoundaryEventScriptTask.bpmn',
            'start_non_interrupting'
        );

        $this->onlyJob(RunScriptTask::class, 'non_interrupting_setup', $nonInterrupting->id)->handle();
        $this->onlyJob(RunScriptTask::class, 'script_non_interrupting', $nonInterrupting->id)->handle();

        $failingToken = $nonInterrupting->tokens()
            ->where('element_id', 'script_non_interrupting')
            ->where('status', 'FAILING')
            ->first();
        $this->assertNotNull($failingToken);
        $this->assertRequestData($nonInterrupting, ['non_interrupting_setup' => true]);
        $this->assertNoRequestLocks($nonInterrupting);
    }

    public function testExc02StopsInlineExecutionAtIntermediateCatchEvent(): void
    {
        Bus::fake();
        $request = $this->startInlineProcess('InlineIntermediateEventChain.bpmn', 'start');

        $this->onlyJob(RunScriptTask::class, 'script_a', $request->id)->handle();
        $this->assertNotNull($this->activeToken($request, 'wait_for_signal'));
        $this->assertCount(0, $this->jobs(RunScriptTask::class, 'script_b', $request->id));

        (new CatchSignalEventInRequest($request->refresh(), [], 'INLINE_CONTINUE_SIGNAL'))->handle();
        $this->onlyJob(RunScriptTask::class, 'script_b', $request->id)->handle();

        $this->assertRequestData($request, [
            'intermediate_script_a' => true,
            'intermediate_script_b' => true,
        ]);
        $this->assertRequestCompleted($request);
        $this->assertNoRequestLocks($request);
    }

    public function testExc03KeepsCollaborationTasksAsynchronousAcrossParticipants(): void
    {
        Bus::fake();
        $participantA = $this->startInlineProcess('InlineCollaborationScriptTasks.bpmn', 'start_a');

        $this->onlyJob(RunScriptTask::class, 'participant_a_script_a', $participantA->id)->handle();
        $this->onlyJob(RunScriptTask::class, 'participant_a_script_b', $participantA->id)->handle();

        $collaborationId = $participantA->refresh()->process_collaboration_id;
        $participantB = ProcessRequest::query()
            ->where('process_collaboration_id', $collaborationId)
            ->whereKeyNot($participantA->id)
            ->firstOrFail();

        $this->onlyJob(RunServiceTask::class, 'participant_b_service', $participantB->id)->handle();
        $this->onlyJob(RunScriptTask::class, 'participant_b_script', $participantB->id)->handle();

        $this->assertRequestData($participantA, [
            'participant_a_script_a' => true,
            'participant_a_script_b' => true,
        ]);
        $this->assertRequestData($participantB, [
            'participant_b_service' => true,
            'participant_b_script' => true,
        ]);
        $this->assertRequestCompleted($participantA);
        $this->assertRequestCompleted($participantB);
        $this->assertNoRequestLocks($participantA, $participantB);
    }

    public function testExc04KeepsCallActivityAndChildRequestAsynchronous(): void
    {
        Bus::fake();
        $child = $this->createInlineProcess('InlineCallActivityChild.bpmn');
        $parent = $this->createInlineProcess('InlineCallActivityChain.bpmn', [
            '[child_process_id]' => (string) $child->id,
        ]);
        $parentRequest = $this->startProcess($parent, 'start');

        $this->onlyJob(RunScriptTask::class, 'script_a', $parentRequest->id)->handle();
        $childRequest = ProcessRequest::where('parent_request_id', $parentRequest->id)->firstOrFail();
        $this->assertCount(0, $this->jobs(RunScriptTask::class, 'script_b', $parentRequest->id));

        $this->onlyJob(RunServiceTask::class, 'child_service', $childRequest->id)->handle();
        $this->onlyJob(RunScriptTask::class, 'script_b', $parentRequest->id)->handle();

        $this->assertRequestData($childRequest, ['call_child_service' => true]);
        $this->assertRequestData($parentRequest, [
            'call_parent_script_a' => true,
            'call_child_service' => true,
            'call_parent_script_b' => true,
        ]);
        $this->assertRequestCompleted($childRequest);
        $this->assertRequestCompleted($parentRequest);
        $this->assertNoRequestLocks($parentRequest, $childRequest);
    }

    public function testExc05KeepsSequentialAndParallelMultiInstanceTasksAsynchronous(): void
    {
        Bus::fake();
        $sequential = $this->startInlineProcess(
            'InlineMultiInstanceAutomatedTask.bpmn',
            'start_sequential',
            ['sequential_items' => [['id' => 1], ['id' => 2], ['id' => 3]]]
        );
        $this->onlyJob(RunScriptTask::class, 'sequential_setup', $sequential->id)->handle();

        for ($index = 0; $index < 3; $index++) {
            $jobs = $this->jobs(RunServiceTask::class, 'sequential_service', $sequential->id);
            $this->assertCount($index + 1, $jobs);
            $jobs->get($index)->handle();
        }

        $this->assertSame(
            ['sequential_service', 'sequential_service', 'sequential_service'],
            InlineTaskTestService::elementRunsForRequest($sequential->id)
        );
        $this->assertRequestCompleted($sequential);
        $this->assertNoRequestLocks($sequential);

        InlineTaskTestService::reset();
        Bus::fake();
        $parallel = $this->startInlineProcess(
            'InlineMultiInstanceAutomatedTask.bpmn',
            'start_parallel',
            ['parallel_items' => [['id' => 1], ['id' => 2], ['id' => 3]]]
        );
        $this->onlyJob(RunServiceTask::class, 'parallel_setup', $parallel->id)->handle();

        $parallelJobs = $this->jobs(RunScriptTask::class, 'parallel_script', $parallel->id);
        $this->assertCount(3, $parallelJobs);
        $parallelJobs->each(fn (RunScriptTask $job) => $job->handle());

        $this->assertCount(
            3,
            $parallel->tokens()->where('element_id', 'parallel_script')->get()
        );
        $this->assertRequestCompleted($parallel);
        $this->assertNoRequestLocks($parallel);
    }

    public function testErr01AttributesFailureAndDoesNotRunSubsequentTask(): void
    {
        Bus::fake();
        $request = $this->startInlineProcess('InlineFailingTaskChain.bpmn', 'start');

        $this->onlyJob(RunScriptTask::class, 'successful_task', $request->id)->handle();

        $failingToken = $request->tokens()
            ->where('element_id', 'failing_task')
            ->where('status', 'FAILING')
            ->first();
        $this->assertNotNull($failingToken);
        $this->assertSame('ERROR', $request->refresh()->status);
        $this->assertTrue($request->data['successful_task_ran'] ?? false);
        $this->assertArrayNotHasKey('must_not_run', $request->data);
        $this->assertFalse($request->tokens()->where('element_id', 'must_not_run')->exists());
        $this->assertCount(0, $this->jobs(RunScriptTask::class, 'must_not_run', $request->id));
        $this->assertNoRequestLocks($request);
    }

    public function testFbk01ReloadsContextAfterExecutionRevisionChanges(): void
    {
        Bus::fake();
        $request = $this->startInlineProcess('InlineRevisionMismatch.bpmn', 'start');
        $this->completeUserTask($request, 'user_task');
        $initialRevision = $request->refresh()->execution_revision;

        InlineRevisionTestService::$duringRun = static function (string $tokenId): void {
            $token = ProcessRequestToken::findOrFail($tokenId);
            $request = $token->processRequest;
            $request->data = array_merge($request->data, ['external_revision_write' => true]);
            $request->execution_revision = (int) $request->execution_revision + 1;
            $request->saveQuietly();
        };

        $this->onlyJob(RunServiceTask::class, 'service_a', $request->id)->handle();
        $scriptJob = $this->jobs(RunScriptTask::class, 'script_b', $request->id)->first();
        $scriptJob?->handle();

        $this->assertRequestData($request, [
            'external_revision_write' => true,
            'revision_service_a' => true,
            'revision_script_b' => true,
        ]);
        $this->assertGreaterThan($initialRevision, $request->refresh()->execution_revision);
        $this->assertRequestCompleted($request);
        $this->assertNoRequestLocks($request);
    }

    public function testExc06DispatchesCustomQueueServiceInsteadOfRunningInline(): void
    {
        Bus::fake();
        $request = $this->startInlineProcess('InlineCustomQueueServiceTask.bpmn', 'start');

        $this->onlyJob(RunScriptTask::class, 'script_a', $request->id)->handle();
        $serviceJob = $this->onlyJob(RunServiceTask::class, 'service_custom', $request->id);

        $this->assertSame('inline-custom-queue', $serviceJob->queue);
        $this->assertSame([], InlineTaskTestService::elementRunsForRequest($request->id));
        $serviceJob->handle();

        $this->assertRequestData($request, [
            'custom_queue_script_a' => true,
            'custom_queue_service' => true,
        ]);
        $this->assertRequestCompleted($request);
        $this->assertNoRequestLocks($request);
    }

    private function startInlineProcess(string $fixture, string $startEvent, array $data = []): ProcessRequest
    {
        return $this->startProcess($this->createInlineProcess($fixture), $startEvent, $data);
    }

    private function createInlineProcess(string $fixture, array $replacements = []): Process
    {
        $bpmn = file_get_contents(__DIR__ . self::FIXTURE_PATH . $fixture);
        $this->assertNotFalse($bpmn);

        return $this->createProcess([
            'bpmn' => strtr($bpmn, $replacements),
        ]);
    }

    private function activeToken(ProcessRequest $request, string $elementId): ProcessRequestToken
    {
        return $request->tokens()
            ->where('element_id', $elementId)
            ->where('status', 'ACTIVE')
            ->firstOrFail();
    }

    private function completeUserTask(ProcessRequest $request, string $elementId): void
    {
        $this->completeTask($this->activeToken($request, $elementId));
        $jobs = Bus::dispatchedSync(CompleteActivity::class);
        $this->assertCount(1, $jobs, 'Expected the user-task completion job to be dispatched synchronously.');
        $jobs->first()->handle();
    }

    private function jobs(string $class, ?string $elementId = null, ?int $requestId = null)
    {
        return Bus::dispatched($class, function ($job) use ($elementId, $requestId) {
            $jobElementId = $job->elementId ?? null;
            if ($elementId !== null && $jobElementId === null && isset($job->tokenId)) {
                $jobElementId = ProcessRequestToken::find($job->tokenId)?->element_id;
            }

            return ($elementId === null || $jobElementId === $elementId)
                && ($requestId === null || (int) $job->instanceId === $requestId);
        })->values();
    }

    private function onlyJob(string $class, string $elementId, int $requestId)
    {
        $jobs = $this->jobs($class, $elementId, $requestId);
        $this->assertCount(1, $jobs, sprintf(
            'Expected one %s job for request %d and element %s.',
            class_basename($class),
            $requestId,
            $elementId
        ));

        return $jobs->first();
    }

    private function assertRequestData(ProcessRequest $request, array $expected): void
    {
        $request->refresh();
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $request->data);
            $this->assertSame($value, $request->data[$key]);
        }
    }

    private function assertRequestCompleted(ProcessRequest $request): void
    {
        $this->assertSame('COMPLETED', $request->refresh()->status);
        $this->assertCount(0, $request->tokens()->where('status', 'ACTIVE')->get());
    }

    private function assertNoRequestLocks(ProcessRequest ...$requests): void
    {
        $requestIds = collect($requests)->pluck('id')->map(fn ($id) => (int) $id);
        $locks = ProcessRequestLock::all()->filter(function (ProcessRequestLock $lock) use ($requestIds) {
            $lockedIds = collect($lock->request_ids ?? [])->map(fn ($id) => (int) $id);

            return $requestIds->contains((int) $lock->request_id)
                || $lockedIds->intersect($requestIds)->isNotEmpty();
        });

        $this->assertCount(0, $locks, 'Expected BPMN request locks to be cleaned up.');
    }
}

class InlineTaskTestService implements ServiceTaskImplementationInterface
{
    private static array $runs = [];

    public static function reset(): void
    {
        self::$runs = [];
    }

    public static function elementRunsForRequest(int $requestId): array
    {
        return collect(self::$runs)
            ->where('request_id', $requestId)
            ->pluck('element_id')
            ->values()
            ->all();
    }

    public function run(array $data, array $config, $tokenId = '')
    {
        $token = ProcessRequestToken::findOrFail($tokenId);
        self::$runs[] = [
            'request_id' => (int) $token->process_request_id,
            'element_id' => $token->element_id,
        ];

        $output = match ($token->element_id) {
            'service_a' => 'seq_service_a',
            'service_b' => 'seq_service_b',
            'service_c' => 'seq_service_c',
            'branch_service' => 'parallel_service',
            'participant_b_service' => 'participant_b_service',
            'child_service' => 'call_child_service',
            'sequential_service' => 'sequential_instance_service',
            'parallel_setup' => 'parallel_setup_service',
            'service_custom' => 'custom_queue_service',
            default => 'service_' . $token->element_id,
        };

        return [$output => true];
    }
}

class InlineTaskTestServiceB extends InlineTaskTestService
{
}

class InlineRevisionTestService extends InlineTaskTestService
{
    public static $duringRun;

    public function run(array $data, array $config, $tokenId = '')
    {
        if (self::$duringRun) {
            (self::$duringRun)((string) $tokenId);
        }

        parent::run($data, $config, $tokenId);

        return ['revision_service_a' => true];
    }
}

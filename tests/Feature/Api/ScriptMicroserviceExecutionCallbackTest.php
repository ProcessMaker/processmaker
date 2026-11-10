<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use Tests\TestCase;

/**
 * The microservice callback must not let a signed-in user complete a task or
 * write request data. These tests describe that boundary: they fail while the
 * handler trusts metadata from any API login, and they pass once a callback
 * is rejected unless it is the in-flight script task this instance dispatched.
 */
class ScriptMicroserviceExecutionCallbackTest extends TestCase
{
    private const CALLBACK = '/api/1.0/scripts/microservice/execution';

    public function testGuestCallbackIsRejected(): void
    {
        $started = $this->startApprovalRequest();

        $response = $this->postJson(self::CALLBACK, $this->successPayload($started, ['forged' => 'guest']));

        $response->assertUnauthorized();
        $this->assertRequestUntouched($started['request'], $started['task'], ['seed' => 'original']);
    }

    public function testOrdinaryUserCannotCompleteAnotherUsersApprovalOrWriteVariables(): void
    {
        $started = $this->startApprovalRequest();
        $attacker = User::factory()->create();

        $response = $this->callbackAs($attacker, $this->successPayload($started, [
            'forgedApproval' => 'approved',
            'seed' => 'overwritten',
        ]));

        $this->assertRequestUntouched($started['request'], $started['task'], ['seed' => 'original']);
        $this->assertSame($started['owner']->id, $started['task']->refresh()->user_id);
        $this->assertCallbackRejected($response);
    }

    public function testTaskAssigneeCannotCompleteThroughTheMicroserviceCallback(): void
    {
        $started = $this->startApprovalRequest();

        $response = $this->callbackAs($started['owner'], $this->successPayload($started, [
            'forgedApproval' => 'approved-by-assignee-via-callback',
        ]));

        $this->assertRequestUntouched($started['request'], $started['task'], ['seed' => 'original']);
        $this->assertCallbackRejected($response);
    }

    public function testAdministratorCannotCompleteThroughTheMicroserviceCallback(): void
    {
        $started = $this->startApprovalRequest();
        $admin = User::factory()->admin()->create();

        $response = $this->callbackAs($admin, $this->successPayload($started, [
            'forgedApproval' => 'approved-by-admin-via-callback',
        ]));

        $this->assertRequestUntouched($started['request'], $started['task'], ['seed' => 'original']);
        $this->assertCallbackRejected($response);
    }

    public function testOrdinaryUserCannotCompleteAnInFlightScriptTask(): void
    {
        $started = $this->startInFlightScriptTask();
        $attacker = User::factory()->create();

        $response = $this->callbackAs($attacker, $this->successPayload($started, [
            'forgedScriptOutput' => 'injected',
            'seed' => 'overwritten',
        ]));

        $this->assertRequestUntouched($started['request'], $started['task'], ['seed' => 'original']);
        $this->assertCallbackRejected($response);
        $this->assertSame('scriptTask', $started['task']->refresh()->element_type);
    }

    public function testScriptTaskMetadataDoesNotAuthorizeADifferentActiveTask(): void
    {
        $script = Script::factory()->create();
        $parallel = $this->startParallelApprovalAndScript($script);
        $attacker = User::factory()->create();
        $approval = $parallel['approval'];
        $scriptTask = $parallel['scriptTask'];

        $response = $this->callbackAs($attacker, [
            'status' => 'success',
            'output' => [
                'forgedApproval' => 'skipped',
                'seed' => 'overwritten',
            ],
            'metadata' => [
                'script_task' => [
                    'script_id' => $script->id,
                    'definition_id' => $parallel['process']->id,
                    'instance_id' => $parallel['request']->id,
                    'token_id' => $approval->id,
                    'authorized_token_id' => $scriptTask->id,
                ],
            ],
        ]);

        $approval->refresh();
        $scriptTask->refresh();
        $request = $parallel['request']->refresh();
        $this->assertSame(ActivityInterface::TOKEN_STATE_ACTIVE, $approval->status);
        $this->assertSame(ActivityInterface::TOKEN_STATE_ACTIVE, $scriptTask->status);
        $this->assertSame('ACTIVE', $request->status);
        $this->assertSame('original', $request->data['seed'] ?? null);
        $this->assertArrayNotHasKey('forgedApproval', $request->data ?? []);
        $this->assertNull($approval->completed_at);
        $this->assertNull($scriptTask->completed_at);
        $this->assertCallbackRejected($response);
    }

    public function testCallbackCannotReplayAClosedTaskOrMarkTheRequestFailed(): void
    {
        $started = $this->startApprovalRequest();
        WorkflowManager::completeTask(
            $started['process'],
            $started['request'],
            $started['task']->refresh(),
            ['seed' => 'finished', 'legitimate' => true]
        );
        $started['request']->refresh();
        $started['task']->refresh();
        $this->assertSame('COMPLETED', $started['request']->status);
        $this->assertNotSame(ActivityInterface::TOKEN_STATE_ACTIVE, $started['task']->status);

        $attacker = User::factory()->create();
        $response = $this->callbackAs($attacker, $this->successPayload($started, [
            'forgedApproval' => 'replayed',
            'seed' => 'rewritten-after-close',
        ]));

        $request = $started['request']->refresh();
        $task = $started['task']->refresh();
        $this->assertSame('COMPLETED', $request->status);
        $this->assertSame('finished', $request->data['seed'] ?? null);
        $this->assertArrayNotHasKey('forgedApproval', $request->data ?? []);
        $this->assertEmpty($request->errors);
        $this->assertNotSame(ActivityInterface::TOKEN_STATE_ACTIVE, $task->status);
        $this->assertCallbackRejected($response);
    }

    public function testOrdinaryUserCannotInjectAScriptPreviewForAnotherUser(): void
    {
        Event::fake([ScriptResponseEvent::class]);
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $response = $this->callbackAs($attacker, [
            'status' => 'success',
            'output' => ['preview' => 'forged'],
            'metadata' => [
                'nonce' => 'attacker-chosen-nonce',
                'current_user' => $victim->id,
            ],
        ]);

        Event::assertNotDispatched(ScriptResponseEvent::class);
        $this->assertCallbackRejected($response);
    }

    /**
     * @param array{process: Process, request: ProcessRequest, task: ProcessRequestToken, owner: User, script?: Script} $started
     * @param array<string, mixed> $output
     * @return array<string, mixed>
     */
    private function successPayload(array $started, array $output): array
    {
        $scriptId = isset($started['script']) ? $started['script']->id : Script::factory()->create()->id;

        return [
            'status' => 'success',
            'output' => $output,
            'metadata' => [
                'script_task' => [
                    'script_id' => $scriptId,
                    'definition_id' => $started['process']->id,
                    'instance_id' => $started['request']->id,
                    'token_id' => $started['task']->id,
                ],
            ],
        ];
    }

    /**
     * @return array{process: Process, request: ProcessRequest, task: ProcessRequestToken, owner: User}
     */
    private function startApprovalRequest(): array
    {
        $owner = User::factory()->create();
        Auth::login($owner);
        $process = $this->createProcessFromBPMN('tests/Fixtures/single_task_with_screen.bpmn', [
            'user_id' => $owner->id,
        ]);
        $start = $process->getDefinitions()->getStartEvent('node_1');
        $request = WorkflowManager::triggerStartEvent($process, $start, ['seed' => 'original']);
        $task = $request->tokens()->where('element_id', 'node_2')->where('status', ActivityInterface::TOKEN_STATE_ACTIVE)->firstOrFail();
        Auth::logout();

        return compact('process', 'request', 'task', 'owner');
    }

    /**
     * @return array{process: Process, request: ProcessRequest, task: ProcessRequestToken, owner: User, script: Script}
     */
    private function startInFlightScriptTask(): array
    {
        $owner = User::factory()->create();
        $script = Script::factory()->create(['run_as_user_id' => $owner->id]);
        Auth::login($owner);
        $bpmn = str_replace(
            '[script_id]',
            (string) $script->id,
            file_get_contents(base_path('tests/Fixtures/script_without_settings.bpmn'))
        );
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
            'user_id' => $owner->id,
        ]);

        $request = $this->withoutRunningScriptTasks(function () use ($process) {
            $start = $process->getDefinitions()->getStartEvent('node_1');

            return WorkflowManager::triggerStartEvent($process, $start, ['seed' => 'original']);
        });

        $task = $request->tokens()->where('element_id', 'node_2')->where('status', ActivityInterface::TOKEN_STATE_ACTIVE)->firstOrFail();
        Auth::logout();

        return compact('process', 'request', 'task', 'owner', 'script');
    }

    /**
     * @return array{process: Process, request: ProcessRequest, approval: ProcessRequestToken, scriptTask: ProcessRequestToken, owner: User}
     */
    private function startParallelApprovalAndScript(Script $script): array
    {
        $owner = User::factory()->create();
        Auth::login($owner);
        $bpmn = str_replace('[script_id]', (string) $script->id, $this->parallelApprovalAndScriptBpmn());
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
            'user_id' => $owner->id,
        ]);

        $request = $this->withoutRunningScriptTasks(function () use ($process) {
            $start = $process->getDefinitions()->getStartEvent('start');

            return WorkflowManager::triggerStartEvent($process, $start, ['seed' => 'original']);
        });

        $approval = $request->tokens()->where('element_id', 'approval')->where('status', ActivityInterface::TOKEN_STATE_ACTIVE)->firstOrFail();
        $scriptTask = $request->tokens()->where('element_id', 'script_task')->where('status', ActivityInterface::TOKEN_STATE_ACTIVE)->firstOrFail();
        Auth::logout();

        return compact('process', 'request', 'approval', 'scriptTask', 'owner');
    }

    /**
     * Keep the script task active. The engine dispatches RunScriptTask after the
     * token is stored; faking only that job leaves the token in flight.
     */
    private function withoutRunningScriptTasks(callable $start): ProcessRequest
    {
        $dispatcher = Bus::getFacadeRoot();
        Bus::fake([RunScriptTask::class]);
        try {
            $request = $start();
        } finally {
            Bus::swap($dispatcher);
        }

        return $request;
    }

    private function callbackAs(User $caller, array $body)
    {
        return $this->actingAs($caller, 'api')->postJson(self::CALLBACK, $body);
    }

    private function assertCallbackRejected($response): void
    {
        $this->assertContains(
            $response->getStatusCode(),
            [401, 403],
            'Microservice callback response: ' . $response->getContent()
        );
    }

    private function assertRequestUntouched(ProcessRequest $request, ProcessRequestToken $task, array $data): void
    {
        $request->refresh();
        $task->refresh();
        $this->assertSame(
            ActivityInterface::TOKEN_STATE_ACTIVE,
            $task->status,
            'Token status is ' . $task->status . ' and request status is ' . $request->status
        );
        $this->assertNull($task->completed_at);
        $this->assertSame('ACTIVE', $request->status, json_encode($request->errors));
        $this->assertEmpty($request->errors);
        foreach ($data as $key => $value) {
            $this->assertSame($value, $request->data[$key] ?? null);
        }
        $this->assertArrayNotHasKey('forgedApproval', $request->data ?? []);
        $this->assertArrayNotHasKey('forgedScriptOutput', $request->data ?? []);
        $this->assertArrayNotHasKey('forged', $request->data ?? []);
    }

    private function parallelApprovalAndScriptBpmn(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd" targetNamespace="http://processmaker.com/tests/microservice-callback">
  <bpmn:process id="parallel_approval_and_script" name="Parallel approval and script" isExecutable="true">
    <bpmn:startEvent id="start" name="Start">
      <bpmn:outgoing>flow_start_split</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:parallelGateway id="split" name="Split" gatewayDirection="Diverging">
      <bpmn:incoming>flow_start_split</bpmn:incoming>
      <bpmn:outgoing>flow_split_approval</bpmn:outgoing>
      <bpmn:outgoing>flow_split_script</bpmn:outgoing>
    </bpmn:parallelGateway>
    <bpmn:task id="approval" name="Approval" pm:assignment="requester">
      <bpmn:incoming>flow_split_approval</bpmn:incoming>
      <bpmn:outgoing>flow_approval_join</bpmn:outgoing>
    </bpmn:task>
    <bpmn:scriptTask id="script_task" name="Script Task" pm:scriptRef="[script_id]">
      <bpmn:incoming>flow_split_script</bpmn:incoming>
      <bpmn:outgoing>flow_script_join</bpmn:outgoing>
    </bpmn:scriptTask>
    <bpmn:parallelGateway id="join" name="Join" gatewayDirection="Converging">
      <bpmn:incoming>flow_approval_join</bpmn:incoming>
      <bpmn:incoming>flow_script_join</bpmn:incoming>
      <bpmn:outgoing>flow_join_end</bpmn:outgoing>
    </bpmn:parallelGateway>
    <bpmn:endEvent id="end" name="End">
      <bpmn:incoming>flow_join_end</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="flow_start_split" sourceRef="start" targetRef="split" />
    <bpmn:sequenceFlow id="flow_split_approval" sourceRef="split" targetRef="approval" />
    <bpmn:sequenceFlow id="flow_split_script" sourceRef="split" targetRef="script_task" />
    <bpmn:sequenceFlow id="flow_approval_join" sourceRef="approval" targetRef="join" />
    <bpmn:sequenceFlow id="flow_script_join" sourceRef="script_task" targetRef="join" />
    <bpmn:sequenceFlow id="flow_join_end" sourceRef="join" targetRef="end" />
  </bpmn:process>
</bpmn:definitions>
XML;
    }
}

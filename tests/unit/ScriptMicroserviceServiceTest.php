<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Services\ScriptMicroserviceService;
use Tests\TestCase;

class ScriptMicroserviceServiceTest extends TestCase
{
    protected ScriptMicroserviceService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScriptMicroserviceService();
        $this->user = User::factory()->create();
    }

    public function testHandlePreviewSuccess()
    {
        Event::fake();

        $request = new Request();
        $request->merge([
            'status' => 'success',
            'output' => ['result' => 'test output'],
            'metadata' => [
                'nonce' => 'test-nonce',
                'current_user' => $this->user->id,
            ],
        ]);

        $this->service->handle($request);

        Event::assertDispatched(ScriptResponseEvent::class, function ($event) {
            return $event->userId === $this->user->id
                && $event->status === 200
                && $event->response['output'] === ['result' => 'test output']
                && $event->nonce === 'test-nonce';
        });
    }

    public function testHandlePreviewError()
    {
        Event::fake();

        $request = new Request();
        $request->merge([
            'status' => 'error',
            'message' => 'Test error message',
            'metadata' => [
                'nonce' => 'test-nonce',
                'current_user' => $this->user->id,
            ],
        ]);

        $this->service->handle($request);

        Event::assertDispatched(ScriptResponseEvent::class, function ($event) {
            return $event->userId === $this->user->id
                && $event->status === 500
                && $event->response['exception'] === 'Test error message'
                && $event->nonce === 'test-nonce';
        });
    }

    public function testHandleScriptTaskSuccess()
    {
        Queue::fake();

        $script = Script::factory()->create();
        $definitions = Definitions::factory()->create();
        $instance = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create();

        $request = new Request();
        $request->merge([
            'status' => 'success',
            'output' => ['result' => 'task output'],
            'metadata' => [
                'script_task' => [
                    'script_id' => $script->id,
                    'definition_id' => $definitions->id,
                    'instance_id' => $instance->id,
                    'token_id' => $token->id,
                ],
            ],
        ]);

        $this->service->handle($request);

        Queue::assertPushedOn('bpmn', CompleteActivity::class, function ($job) use ($definitions, $instance, $token) {
            return $job->definitionsId === $definitions->id
                && $job->instanceId === $instance->id
                && $job->tokenId === $token->id
                && $job->data === ['result' => 'task output'];
        });
    }

    public function testHandleScriptTaskError()
    {
        Queue::fake();

        $script = Script::factory()->create();
        $definitions = Definitions::factory()->create();
        $instance = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create();

        $request = new Request();
        $request->merge([
            'status' => 'error',
            'output' => ['error' => 'Task failed'],
            'metadata' => [
                'script_task' => [
                    'script_id' => $script->id,
                    'definition_id' => $definitions->id,
                    'instance_id' => $instance->id,
                    'token_id' => $token->id,
                ],
            ],
        ]);

        $this->service->handle($request);

        Queue::assertNotPushed(CompleteActivity::class);
    }
}

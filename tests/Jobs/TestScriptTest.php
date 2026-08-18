<?php

namespace Tests\Jobs;

use Illuminate\Support\Facades\Event;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Jobs\TestScript;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use Tests\TestCase;

class TestScriptTest extends TestCase
{
    public function testRealtimeExecutorBroadcastsOnlyTheScriptOutput()
    {
        config(['script-runner-microservice.enabled' => true]);
        Event::fake([ScriptResponseEvent::class]);

        $script = $this->realtimeScript([
            'status' => 'success',
            'metadata' => ['script_id' => 24, 'executor_type' => 'realtime'],
            'output' => ['response' => 1],
        ]);

        (new TestScript($script, $this->previewUser(), 'return [];', [], [], '123abc'))->handle();

        Event::assertDispatched(ScriptResponseEvent::class, function (ScriptResponseEvent $event) {
            return $event->status === 200
                && $event->response === ['output' => ['response' => 1]]
                && $event->nonce === '123abc';
        });
    }

    public function testRealtimeExecutorBroadcastsFailureAsException()
    {
        config(['script-runner-microservice.enabled' => true]);
        Event::fake([ScriptResponseEvent::class]);

        $script = $this->realtimeScript([
            'status' => 'error',
            'metadata' => ['script_id' => 24],
            'error' => 'Syntax error, unexpected token',
        ]);

        (new TestScript($script, $this->previewUser(), 'return [];', [], [], '123abc'))->handle();

        Event::assertDispatched(ScriptResponseEvent::class, function (ScriptResponseEvent $event) {
            return $event->status === 500
                && $event->response['message'] === 'Syntax error, unexpected token'
                && !array_key_exists('metadata', $event->response);
        });
    }

    private function realtimeScript(array $microserviceResponse): Script
    {
        return new class($microserviceResponse) extends Script {
            private array $microserviceResponse;

            public function __construct(array $microserviceResponse = [])
            {
                parent::__construct();
                $this->microserviceResponse = $microserviceResponse;
            }

            public function getScriptExecutorAttribute()
            {
                return new ScriptExecutor([
                    'type' => ScriptExecutorType::Realtime,
                ]);
            }

            public function runScript(array $data, array $config, $tokenId = '', $timeout = null, $sync = 1, $metadata = [])
            {
                return $this->microserviceResponse;
            }
        };
    }

    private function previewUser(): User
    {
        $user = new User();
        $user->id = 123;

        return $user;
    }
}

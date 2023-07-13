<?php

namespace Tests\Feature\Processes;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Notification;
use Mockery;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ErrorExecutionNotification;
use ProcessMaker\Notifications\NotificationChannel;
use ProcessMaker\ScriptRunners\MockRunner;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ErrorExecutionNotificationTest extends TestCase
{
    use RequestHelper;

    private function startProcess($manager, $errorHandling)
    {
        $script = Script::factory()->create([]);
        $bpmn = file_get_contents(__DIR__ . '/../../Fixtures/ScriptWithErrorHandling.bpmn');
        $bpmn = str_replace('[script_id]', $script->id, $bpmn);
        $errorHandlingValue = str_replace('"', '&#34;', json_encode($errorHandling));
        $bpmn = str_replace('[error_handling]', $errorHandlingValue, $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
            'properties' => [
                'manager_id' => $manager ? $manager->id : null,
            ],
        ]);
        $event = $process->getDefinitions()->getEvent('node_45');

        return WorkflowManager::triggerStartEvent($process, $event, []);
    }

    private function mockScriptRunner()
    {
        $mock = Mockery::mock(MockRunner::class);
        $mock->shouldReceive('setTokenId');
        $mock->shouldReceive('run')->andReturnUsing(function () {
            throw new \RuntimeException('Some error');
        });

        app()->bind(MockRunner::class, function () use ($mock) {
            return $mock;
        });
    }

    public function testNoManager()
    {
        Notification::fake();
        $this->mockScriptRunner();
        $request = $this->startProcess(null, ['inapp_notification' => true, 'email_notification' => false]);
        $error = $request->refresh()->errors[0];
        $this->assertEquals('Some error', $error['message']);

        Notification::assertNothingSent();
    }

    public function testInAppNotificationFromScript()
    {
        Notification::fake();
        $this->mockScriptRunner();
        $manager = User::factory()->create();
        $request = $this->startProcess($manager, ['inapp_notification' => true, 'email_notification' => false]);
        $error = $request->refresh()->errors[0];
        $this->assertEquals('Some error', $error['message']);

        Notification::assertSentTo(
            $manager,
            function (ErrorExecutionNotification $notification, $channels) {
                return in_array(NotificationChannel::class, $channels) &&
                    in_array('broadcast', $channels) &&
                    !in_array('mail', $channels);
            }
        );
    }

    public function testMailNotificationFromScript()
    {
        Notification::fake();
        $this->mockScriptRunner();
        $manager = User::factory()->create();
        $this->startProcess($manager, ['inapp_notification' => false, 'email_notification' => true]);

        Notification::assertSentTo(
            $manager,
            function (ErrorExecutionNotification $notification, $channels) {
                return !in_array(NotificationChannel::class, $channels) &&
                    !in_array('broadcast', $channels) &&
                    in_array('mail', $channels);
            }
        );
    }

    // public function testInAppNotificationFromDataSource()
    // {
    //     app()->bind(Client::class, function () {
    //         $exception = new RequestException('Error Communicating with Server', new Request('GET', 'test'));
    //         $handlerStack = HandlerStack::create(new MockHandler([
    //             $exception,
    //         ]));
    //         return new Client(['handler' => $handlerStack]);
    //     });
    // }
}

<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessWebhook;

class WebhookTest extends TestCase
{
    use RequestHelper;

    public function testStartEventFromWebhook() {
        $process = factory(Process::class)->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn'),
        ]);

        $webhook = factory(ProcessWebhook::class)->create([
            'process_id' => $process->id,
            'node' => 'StartEventUID',
            'token' => 'abc123',
        ]);

        $response = $this->post($webhook->url(), ['someData' => 'something'], ['Accept' => 'application/json']);
        $response->assertStatus(201);
        
        $request = $process->requests->first();
        $this->assertEquals(['someData' => 'something'], $request->data);
    }

}

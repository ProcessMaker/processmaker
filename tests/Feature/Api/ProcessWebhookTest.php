<?php
namespace Tests\Feature\Api;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\ProcessWebhook;

class ProcessWebhookTest extends TestCase
{
    use RequestHelper;
    
    public function testGetWebhook()
    {
        $process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);

        $route = route('api.process_webhooks.show', [
            'id' => $process->id,
            'node' => 'node_1'
        ]);
        $response = $this->apiCall('GET', $route);
        $response->assertJson([]);

        factory(ProcessWebhook::class)->create([
            'process_id' => $process->id,
            'node' => 'node_1',
            'token' => 'abc123',
        ]);

        $response = $this->apiCall('GET', $route);
        $json = $response->json();
        $response->assertJson(['webhook' => [
            'process_id' => $process->id,
            'node' => 'node_1',
            'url' => route('webhook.start_event', ['token' => 'abc123'])
        ]]);
    }

    public function testCreateWebhook()
    {
        $process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);
        $route = route('api.process_webhooks.show', [
            'id' => $process->id,
            'node' => 'node_1',
        ]);
        $response = $this->apiCall('POST', $route);

        $webhook = ProcessWebhook::first();
        $this->assertEquals($process->id, $webhook->process_id);
        $this->assertEquals('node_1', $webhook->node);
        $this->assertRegExp('/[a-zA-Z0-9-]{36}/', $webhook->token);
        
        $response->assertJson(['webhook' => [
            'process_id' => $process->id,
            'node' => 'node_1',
            'url' => route('webhook.start_event', ['token' => $webhook->token])
        ]]);
    }

    public function testDeleteWebhook()
    {
        $process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);

        $route = route('api.process_webhooks.destroy', [
            'id' => $process->id,
            'node' => 'node_1'
        ]);
        
        $response = $this->apiCall('DELETE', $route);
        $response->assertStatus(404);

        factory(ProcessWebhook::class)->create([
            'process_id' => $process->id,
            'node' => 'node_1',
            'token' => 'abc123',
        ]);
        
        $this->assertEquals(1, ProcessWebhook::count());

        $response = $this->apiCall('DELETE', $route);
        $response->assertStatus(204);

        $this->assertEquals(0, ProcessWebhook::count());
    }
}
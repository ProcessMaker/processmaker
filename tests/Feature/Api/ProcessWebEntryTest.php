<?php
namespace Tests\Feature\Api;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\ProcessWebEntry;

class ProcessWebEntryTest extends TestCase
{
    use RequestHelper;
    
    public function testGetWebEntry()
    {
        $process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);

        $route = route('api.process_web_entries.show', [
            'id' => $process->id,
            'node' => 'node_1'
        ]);
        $response = $this->apiCall('GET', $route);
        $response->assertJson([]);

        factory(ProcessWebEntry::class)->create([
            'process_id' => $process->id,
            'node' => 'node_1',
            'token' => 'abc123',
        ]);

        $response = $this->apiCall('GET', $route);
        $json = $response->json();
        $response->assertJson(['web_entry' => [
            'process_id' => $process->id,
            'node' => 'node_1',
            'url' => route('web_entry.start_event', ['token' => 'abc123'])
        ]]);
    }

    public function testCreateWebEntry()
    {
        $process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);
        $route = route('api.process_web_entries.show', [
            'id' => $process->id,
            'node' => 'node_1',
        ]);
        $response = $this->apiCall('POST', $route);

        $web_entry = ProcessWebEntry::first();
        $this->assertEquals($process->id, $web_entry->process_id);
        $this->assertEquals('node_1', $web_entry->node);
        $this->assertRegExp('/[a-zA-Z0-9-]{36}/', $web_entry->token);
        
        $response->assertJson(['web_entry' => [
            'process_id' => $process->id,
            'node' => 'node_1',
            'url' => route('web_entry.start_event', ['token' => $web_entry->token])
        ]]);
    }

    public function testDeleteWebEntry()
    {
        $process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);

        $route = route('api.process_web_entries.destroy', [
            'id' => $process->id,
            'node' => 'node_1'
        ]);
        
        $response = $this->apiCall('DELETE', $route);
        $response->assertStatus(404);

        factory(ProcessWebEntry::class)->create([
            'process_id' => $process->id,
            'node' => 'node_1',
            'token' => 'abc123',
        ]);
        
        $this->assertEquals(1, ProcessWebEntry::count());

        $response = $this->apiCall('DELETE', $route);
        $response->assertStatus(204);

        $this->assertEquals(0, ProcessWebEntry::count());
    }
}
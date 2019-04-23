<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessWebEntry;

class WebEntryTest extends TestCase
{
    use RequestHelper;

    public function testStartEventFromWebEntry() {
        $process = factory(Process::class)->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn'),
        ]);

        $web_entry= factory(ProcessWebEntry::class)->create([
            'process_id' => $process->id,
            'node' => 'StartEventUID',
            'token' => 'abc123',
        ]);

        $response = $this->post($web_entry->url(), ['someData' => 'something'], ['Accept' => 'application/json']);
        $response->assertStatus(201);
        
        $request = $process->requests->first();
        $this->assertTrue(array_key_exists('someData', $request->data));
        $this->assertEquals('something', $request->data['someData']);
    }

}

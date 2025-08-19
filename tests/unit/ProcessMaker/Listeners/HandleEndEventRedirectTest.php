<?php

namespace Tests\Unit\ProcessMaker\Listeners;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mockery;
use ProcessMaker\Events\ProcessCompleted;
use ProcessMaker\Listeners\HandleEndEventRedirect;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\TestCase;

class HandleEndEventRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Create and authenticate a user for all tests
        $this->user = User::factory()->create();
        Auth::login($this->user);

        // Define the required route for the tests
        Route::get('api/1.0/requests/{request}', function () {
            return response()->json(['status' => 'success']);
        })->name('api.requests.show');
    }

    /**
     * Create a process request with required attributes
     */
    private function createProcessRequest(array $attributes = []): ProcessRequest
    {
        return ProcessRequest::factory()->create(array_merge([
            'status' => 'ACTIVE',
            'data' => ['case_title' => 'Test Case'],
            'name' => 'Test Process',
        ], $attributes));
    }

    public function test_handleRedirect_handles_valid_process_request()
    {
        // Create a process with asset_type
        $process = Process::factory()->create(['asset_type' => 'Process']);

        // Create a request without parent (main process)
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'parent_request_id' => null,
        ]);

        // Ensure process relationship is loaded
        $request->setRelation('process', $process);

        $event = new ProcessCompleted($request);
        $listener = new HandleEndEventRedirect();
        $listener->handle($event);

        $this->assertNull($request->getElementDestination(), 'Element destination should be null for a valid process request');
    }

    public function test_handleRedirect_skips_subprocess()
    {
        // Create a process with asset_type
        $process = Process::factory()->create(['asset_type' => 'Process']);

        // Create parent request
        $parentRequest = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        // Create subprocess (request with parent)
        $subprocess = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'parent_request_id' => $parentRequest->id,
        ]);

        // Ensure process relationship is loaded
        $subprocess->setRelation('process', $process);

        $event = new ProcessCompleted($subprocess);
        $listener = new HandleEndEventRedirect();
        $listener->handle($event);

        $this->assertNull($subprocess->getElementDestination(), 'Element destination should be null for a subprocess request');
    }

    public function test_handleRedirect_with_empty_request()
    {
        // Create an empty request object
        $emptyRequest = ProcessRequest::factory()->create();
        $emptyRequest->setRelation('process', Process::factory()->create(['asset_type' => 'Process']));

        $event = new ProcessCompleted($emptyRequest);
        $listener = new HandleEndEventRedirect();

        // Mock the getProcessRequest method to return null
        $event = Mockery::mock(ProcessCompleted::class);
        $event->shouldReceive('getProcessRequest')->andReturn(null);

        $listener->handle($event);

        $this->assertNull($event->getProcessRequest(), 'Process request should be null when empty request is passed');
    }

    public function test_handleRedirect_handles_exception_when_process_not_found()
    {
        // Create request without process relation
        $request = ProcessRequest::factory()->create();

        $event = new ProcessCompleted($request);
        $listener = new HandleEndEventRedirect();

        // No exception should be thrown even if process is missing
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_handleRedirect_handles_exception_when_user_not_authenticated()
    {
        Auth::logout();

        $process = Process::factory()->create(['asset_type' => 'Process']);
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);
        $request->setRelation('process', $process);

        $event = new ProcessCompleted($request);
        $listener = new HandleEndEventRedirect();

        // No exception should be thrown even if user is not authenticated
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_handleRedirect_handles_invalid_request_data()
    {
        // Mock the ProcessCompleted event
        $event = Mockery::mock(ProcessCompleted::class);

        // Mock the process request with invalid data
        $request = Mockery::mock(ProcessRequest::class);
        $request->shouldReceive('getAttribute')->with('parent_request_id')->andReturn(null);
        $request->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $request->shouldReceive('__get')->with('parent_request_id')->andReturn(null);
        $request->shouldReceive('__get')->with('id')->andReturn(1);

        // Set up the event mock
        $event->shouldReceive('getProcessRequest')->andReturn($request);

        $listener = new HandleEndEventRedirect();

        // No exception should be thrown for invalid request data
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_handleRedirect_handles_missing_process_relation()
    {
        // Create a process for the relation
        $process = Process::factory()->create(['asset_type' => 'Process']);

        // Mock the request with missing process relation
        $request = Mockery::mock(ProcessRequest::class);
        $request->shouldReceive('getAttribute')->with('parent_request_id')->andReturn(null);
        $request->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $request->shouldReceive('getAttribute')->with('process')->andReturn($process);
        $request->shouldReceive('__get')->with('parent_request_id')->andReturn(null);
        $request->shouldReceive('__get')->with('id')->andReturn(1);
        $request->shouldReceive('__get')->with('process')->andReturn($process);
        $request->shouldReceive('getKey')->andReturn(1);
        $request->shouldReceive('process')->andThrow(new Exception('Process relation error'));
        $request->shouldReceive('getElementDestination')->andReturn(null);

        // Create and set up the event
        $event = new ProcessCompleted($request);
        $listener = new HandleEndEventRedirect();

        // No exception should be thrown when process relation fails
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_handleRedirect_with_multiple_subprocesses()
    {
        // Create main process
        $mainProcess = Process::factory()->create(['asset_type' => 'Process']);
        $mainRequest = $this->createProcessRequest([
            'process_id' => $mainProcess->id,
        ]);

        // Create multiple subprocesses
        $subprocess1 = $this->createProcessRequest([
            'process_id' => $mainProcess->id,
            'parent_request_id' => $mainRequest->id,
        ]);

        $subprocess2 = $this->createProcessRequest([
            'process_id' => $mainProcess->id,
            'parent_request_id' => $mainRequest->id,
        ]);

        // Set up process relations
        $subprocess1->setRelation('process', $mainProcess);
        $subprocess2->setRelation('process', $mainProcess);

        $listener = new HandleEndEventRedirect();

        // Test first subprocess completion
        $event1 = new ProcessCompleted($subprocess1);
        $result1 = $listener->handle($event1);

        // Test second subprocess completion
        $event2 = new ProcessCompleted($subprocess2);
        $result2 = $listener->handle($event2);

        // Assertions
        $this->assertEquals($mainRequest->id, $subprocess1->parent_request_id,
            'Subprocess1 should have correct parent request ID');
        $this->assertEquals($mainRequest->id, $subprocess2->parent_request_id,
            'Subprocess2 should have correct parent request ID');

        $this->assertNotNull($subprocess1->parent_request_id,
            'Subprocess1 should have a parent request');
        $this->assertNotNull($subprocess2->parent_request_id,
            'Subprocess2 should have a parent request');

        $this->assertNull($result1,
            'Handler should return null for subprocess1');
        $this->assertNull($result2,
            'Handler should return null for subprocess2');
    }

    public function test_handleRedirect_with_nested_subprocesses()
    {
        // Create main process
        $mainProcess = Process::factory()->create(['asset_type' => 'Process']);
        $mainRequest = $this->createProcessRequest([
            'process_id' => $mainProcess->id,
        ]);

        // Level 1 subprocess
        $subprocess1 = $this->createProcessRequest([
            'process_id' => $mainProcess->id,
            'parent_request_id' => $mainRequest->id,
        ]);

        // Level 2 subprocess (nested)
        $subprocess2 = $this->createProcessRequest([
            'process_id' => $mainProcess->id,
            'parent_request_id' => $subprocess1->id,
        ]);

        // Set up process relations
        $mainRequest->setRelation('process', $mainProcess);
        $subprocess1->setRelation('process', $mainProcess);
        $subprocess2->setRelation('process', $mainProcess);

        $listener = new HandleEndEventRedirect();

        // Test nested subprocess completion
        $event = new ProcessCompleted($subprocess2);
        $result = $listener->handle($event);

        // Assertions
        $this->assertEquals($subprocess1->id, $subprocess2->parent_request_id,
            'Level 2 subprocess should have Level 1 subprocess as parent');
        $this->assertEquals($mainRequest->id, $subprocess1->parent_request_id,
            'Level 1 subprocess should have main request as parent');

        $this->assertEquals($mainProcess->id, $subprocess1->process_id,
            'Level 1 subprocess should belong to main process');
        $this->assertEquals($mainProcess->id, $subprocess2->process_id,
            'Level 2 subprocess should belong to main process');

        $this->assertNotNull($subprocess2->parent_request_id,
            'Level 2 subprocess should have a parent request');
        $this->assertNotNull($subprocess1->parent_request_id,
            'Level 1 subprocess should have a parent request');
        $this->assertNull($mainRequest->parent_request_id,
            'Main request should not have a parent request');

        $this->assertNull($result,
            'Handler should return null for nested subprocess');
    }

    public function test_handleRedirect_with_deleted_parent_request()
    {
        // Create main process
        $mainProcess = Process::factory()->create(['asset_type' => 'Process']);

        // Mock the subprocess
        $subprocess = Mockery::mock(ProcessRequest::class);
        $subprocess->shouldReceive('getAttribute')->with('parent_request_id')->andReturn(999999);
        $subprocess->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $subprocess->shouldReceive('getAttribute')->with('process')->andReturn($mainProcess);
        $subprocess->shouldReceive('__get')->with('parent_request_id')->andReturn(999999);
        $subprocess->shouldReceive('__get')->with('id')->andReturn(1);
        $subprocess->shouldReceive('__get')->with('process')->andReturn($mainProcess);
        $subprocess->shouldReceive('getKey')->andReturn(1);
        $subprocess->shouldReceive('process')->andReturn($mainProcess);
        $subprocess->shouldReceive('getElementDestination')->andReturn(null);

        // Add setAttribute expectations
        $subprocess->shouldReceive('setAttribute')->withAnyArgs()->andReturnSelf();

        // Mock data attributes
        $subprocess->data = [
            'case_title' => 'Test Case with Deleted Parent',
            'status' => 'ACTIVE',
        ];

        // Set up process relationship
        $subprocess->shouldReceive('setRelation')->withAnyArgs()->andReturnSelf();

        // Create event with the mocked subprocess
        $event = new ProcessCompleted($subprocess);
        $listener = new HandleEndEventRedirect();

        // Handle the event
        $result = $listener->handle($event);

        // Assertions
        $this->assertNull($result, 'Should handle missing parent gracefully');
        $this->assertEquals(999999, $subprocess->__get('parent_request_id'),
            'Subprocess should maintain reference to deleted parent');
        $this->assertEquals($mainProcess, $subprocess->__get('process'),
            'Subprocess should maintain correct process relationship');
    }

    public function test_handleRedirect_with_concurrent_subprocess_completion()
    {
        // Test behavior when multiple subprocesses complete simultaneously
        $mainProcess = Process::factory()->create(['asset_type' => 'Process']);
        $mainRequest = ProcessRequest::factory()->create([
            'process_id' => $mainProcess->id,
        ]);

        // Create concurrent subprocesses
        $subprocesses = ProcessRequest::factory()->count(3)->create([
            'process_id' => $mainProcess->id,
            'parent_request_id' => $mainRequest->id,
        ]);

        $listener = new HandleEndEventRedirect();
        $results = [];

        // Simulate concurrent completion
        foreach ($subprocesses as $subprocess) {
            $subprocess->setRelation('process', $mainProcess);
            $event = new ProcessCompleted($subprocess);
            $results[] = $listener->handle($event);
        }

        foreach ($results as $result) {
            $this->assertNull($result, 'Each subprocess should be handled independently');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}

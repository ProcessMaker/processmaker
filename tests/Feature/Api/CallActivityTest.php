<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScriptExecutor;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CallActivityTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    /**
     * Tests the a process with call activity to a external process definition
     *
     * @group process_tests
     */
    public function testCallActivity()
    {
        // Script task requires passport installed (oauth token)
        Artisan::call('passport:install', ['-vvv' => true]);

        // Create the processes
        $child = $this->createProcess([
            'id' => 2,
            'bpmn' => file_get_contents(__DIR__ . '/processes/child.bpmn'),
        ]);

        $parent = $this->createProcess([
            'id' => 1,
            'bpmn' => str_replace(
                ['[child_id]', '[start_event_id]'],
                [$child->id, 'node_8'],
                file_get_contents(__DIR__ . '/processes/parent.bpmn')
            ),
        ]);

        // Start a process instance
        $instance = $this->startProcess($parent, 'node_1');
        $subInstance = ProcessRequest::get()[1];

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();
        $activeSubTokens = $subInstance->tokens()->where('status', 'ACTIVE')->get();

        // Assert both processes are COMPLETED
        $this->assertCount(0, $activeTokens);
        $this->assertCount(0, $activeSubTokens);
        error_log(json_encode($instance->errors));
        $this->assertEquals('COMPLETED', $instance->status);
        $this->assertEquals('COMPLETED', $subInstance->status);
    }

    public function testCallActivityFiles()
    {
        // Create the processes
        $child = $this->createProcess([
            'id' => 2,
            'bpmn' => file_get_contents(__DIR__ . '/processes/child-files.bpmn'),
        ]);
        $parent = $this->createProcess([
            'id' => 1,
            'bpmn' => str_replace(
                ['[child_id]', '[start_event_id]'],
                [$child->id, 'startevent'],
                file_get_contents(__DIR__ . '/processes/parent-files.bpmn')
            ),
        ]);
        $instance = $this->startProcess($parent, 'node_1');
        $parentTask = $instance->tokens->where('status', 'ACTIVE')->first();

        $route = route('api.requests.files.store', [$instance->id]);
        $response = $this->apiCall('POST', $route, [
            'file' => File::image('photo1.jpg'),
            'data_name' => 'photo1',
            'parent' => 99,
            'row_id' => 0,
        ]);
        $response = $this->apiCall('POST', $route, [
            'file' => File::image('photo-no-parent.jpg'),
            'data_name' => 'photo1',
        ]);

        $this->completeTask($parentTask);

        $this->assertCount(2, $instance->getMedia());
        $this->assertEquals('photo1', $instance->getMedia()[0]->getCustomProperty('data_name'));
        $this->assertEquals(99, $instance->getMedia()[0]->getCustomProperty('parent'));
        $this->assertEquals('photo1', $instance->getMedia()[1]->getCustomProperty('data_name'));
        $this->assertEquals(0, $instance->getMedia()[1]->getCustomProperty('parent'));

        $childInstance = ProcessRequest::where('parent_request_id', $instance->id)->first();

        $this->assertCount(2, $childInstance->getMedia());
        $copiedFile1 = $childInstance->getMedia()[0];
        $copiedFile2 = $childInstance->getMedia()[1];

        $this->assertEquals('photo1', $copiedFile1->getCustomProperty('data_name'));
        $this->assertEquals(99, $copiedFile1->getCustomProperty('parent'));
        $this->assertEquals('photo1', $copiedFile2->getCustomProperty('data_name'));
        $this->assertEquals(0, $copiedFile2->getCustomProperty('parent'));

        $childTask = $childInstance->tokens->where('status', 'ACTIVE')->first();

        $route = route('api.requests.files.store', [$childInstance->id]);
        $response = $this->apiCall('POST', $route, [
            'file' => File::image('child.jpg'),
            'data_name' => 'child_photo',
        ]);

        // This should overwrite the parent when the call activity finishes
        $route = route('api.requests.files.store', [$childInstance->id]);
        $response = $this->apiCall('POST', $route, [
            'file' => File::image('overwrite.jpg'),
            'data_name' => 'photo1',
            'parent' => 99,
            'row_id' => 0,
        ]);

        $this->completeTask($childTask);

        $instance->refresh();
        $this->assertCount(3, $instance->getMedia());
        $file1 = $instance->getMedia()[0];
        $file2 = $instance->getMedia()[1];
        $file3 = $instance->getMedia()[2];

        $this->assertEquals('photo1', $file1->getCustomProperty('data_name'));
        $this->assertEquals(null, $file1->getCustomProperty('parent'));
        $this->assertEquals('photo-no-parent.jpg', $file1->file_name);

        $this->assertEquals('child_photo', $file2->getCustomProperty('data_name'));
        $this->assertEquals(null, $file2->getCustomProperty('parent'));
        $this->assertEquals('child.jpg', $file2->file_name);

        $this->assertEquals('photo1', $file3->getCustomProperty('data_name'));
        $this->assertEquals(99, $file3->getCustomProperty('parent'));
        $this->assertEquals('overwrite.jpg', $file3->file_name);
    }

    public function testCallActivityWithUpdateInProgress()
    {
        // Script task requires passport installed (oauth token)
        Artisan::call('passport:install', ['-vvv' => true]);

        // Create the processes
        $child = $this->createProcess([
            'id' => 2,
            'bpmn' => file_get_contents(__DIR__ . '/processes/child-with-form-task.bpmn'),
        ]);

        $parent = $this->createProcess([
            'id' => 1,
            'bpmn' => str_replace(
                ['[child_id]', '[start_event_id]'],
                [$child->id, 'node_8'],
                file_get_contents(__DIR__ . '/processes/parent.bpmn')
            ),
        ]);

        // Start a process instance
        $instance = $this->startProcess($parent, 'node_1');
        $subInstance = ProcessRequest::where('parent_request_id', $instance->id)->firstOrFail();

        $activeTask = $subInstance->tokens()->where('status', 'ACTIVE')->firstOrFail();
        $response = $this->apiCall('PUT', route('api.tasks.update', [$activeTask]), [
            'status' => 'COMPLETED',
            'data' => [],
        ]);

        $activeTokens = $instance->refresh()->tokens()->where('status', 'ACTIVE')->get();
        $activeSubTokens = $subInstance->refresh()->tokens()->where('status', 'ACTIVE')->get();

        // Assert both processes are COMPLETED
        $this->assertCount(0, $activeTokens);
        $this->assertCount(0, $activeSubTokens);
        $this->assertEquals('COMPLETED', $instance->status);
        $this->assertEquals('COMPLETED', $subInstance->status);

        /**
         * Start the same process again, this time update the child process
         * while it's in progress.
         */

        // Start the process instance again
        $instance = $this->startProcess($parent, 'node_1');
        $subInstance = ProcessRequest::where('parent_request_id', $instance->id)->firstOrFail();

        // Update the process to create a new process version
        $child->description = 'updated';
        $child->saveOrFail();

        // Now complete the task, same as before
        $activeTask = $subInstance->tokens()->where('status', 'ACTIVE')->firstOrFail();
        $response = $this->apiCall('PUT', route('api.tasks.update', [$activeTask]), [
            'status' => 'COMPLETED',
            'data' => [],
        ]);

        $activeTokens = $instance->refresh()->tokens()->where('status', 'ACTIVE')->get();
        $activeSubTokens = $subInstance->refresh()->tokens()->where('status', 'ACTIVE')->get();

        /**
         * Fails. Active token count should be zero like the first instance.
         * This passes if you comment out `$child->saveOrFail();` above
         */
        $this->assertCount(0, $activeTokens);
        $this->assertCount(0, $activeSubTokens);
        $this->assertEquals('COMPLETED', $instance->status);
        $this->assertEquals('COMPLETED', $subInstance->status);
    }

    public function testCallActivityValidation()
    {
        $child = $this->createProcess([
            'id' => 29,
            'bpmn' => file_get_contents(__DIR__ . '/processes/SignalStartEvent.bpmn'),
        ]);
        $parent = $this->createProcess([
            'id' => 30,
            'bpmn' => file_get_contents(__DIR__ . '/processes/ParentCallActivity.bpmn'),
        ]);
        // Process should have one warning related to "The start event of the call activity is not empty"
        $this->assertEquals([[
            'title' => 'Invalid process',
            'text' => 'The start event of the call activity is not empty',
        ]], $parent->warnings);
    }

    public function testCallActivityValidationToWebEntryStartEvent()
    {
        $child = $this->createProcess([
            'id' => 29,
            'bpmn' => file_get_contents(__DIR__ . '/processes/WebEntryStartEvent.bpmn'),
        ]);
        $parent = $this->createProcess([
            'id' => 30,
            'bpmn' => file_get_contents(__DIR__ . '/processes/ParentCallActivity.bpmn'),
        ]);
        // Process should have one warning related to "The start event of the call activity can not be a web entry"
        $this->assertEquals([[
            'title' => 'Invalid process',
            'text' => 'The start event of the call activity can not be a web entry',
        ]], $parent->warnings);
    }

    public function testCallActivityValidationToNonStartEventElement()
    {
        $child = $this->createProcess([
            'id' => 29,
            'bpmn' => file_get_contents(__DIR__ . '/processes/WebEntryStartEvent.bpmn'),
        ]);
        $parentBpmn = file_get_contents(__DIR__ . '/processes/ParentCallActivity.bpmn');
        // Point to a EndEvent instead of StartEvent
        $parentBpmn = str_replace('&#34;startEvent&#34;:&#34;node_2&#34;', '&#34;startEvent&#34;:&#34;node_3&#34;', $parentBpmn);
        $parent = $this->createProcess([
            'id' => 30,
            'bpmn' => $parentBpmn,
        ]);
        // Process should have one warning related to "The start event of the call activity is not a start event"
        $this->assertEquals([[
            'title' => 'Invalid process',
            'text' => 'The start event of the call activity is not a start event',
        ]], $parent->warnings);
    }

    public function testCallActivityValidationToDeletedElement()
    {
        $child = $this->createProcess([
            'id' => 29,
            'bpmn' => file_get_contents(__DIR__ . '/processes/WebEntryStartEvent.bpmn'),
        ]);
        $parentBpmn = file_get_contents(__DIR__ . '/processes/ParentCallActivity.bpmn');
        // Point to a EndEvent instead of StartEvent
        $parentBpmn = str_replace('&#34;startEvent&#34;:&#34;node_2&#34;', '&#34;startEvent&#34;:&#34;deleted_node_id&#34;', $parentBpmn);
        $parent = $this->createProcess([
            'id' => 30,
            'bpmn' => $parentBpmn,
        ]);
        // Process should have one warning related to "The start event with id "deleted_node_id" does not exist"
        $this->assertEquals([[
            'title' => 'Invalid process',
            'text' => 'The start event with id "deleted_node_id" does not exist',
        ]], $parent->warnings);
    }

    public function testProcessLoop()
    {
        // Script task requires passport installed (oauth token)
        Artisan::call('passport:install', ['-vvv' => true]);

        // Create the processes
        $process = $this->createProcess([
            'id' => 2,
            'bpmn' => file_get_contents(__DIR__ . '/processes/ProcessLoop.bpmn'),
        ]);

        // Start a process instance
        $instance = $this->startProcess($process, 'node_1');

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        $this->completeTask($activeTokens[0], ['input_1' => 1]);

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        $this->completeTask($activeTokens[0], ['input_2' => 2]);

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        $this->completeTask($activeTokens[0], ['input_3' => 3]);

        $activeTokens[0]->refresh();

        // Get active tokens
        $instance->refresh();
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // Assert both processes are COMPLETED
        $this->assertCount(0, $activeTokens);
        $this->assertEquals('COMPLETED', $instance->status);

        //Remove parameter loopCharateristics
        $data = $instance->data;
        unset($data['loopCharacteristics']);

        // verify data
        $this->assertEquals(['input_1' => 1, 'input_2' => 2, 'input_3' => 3], $data);
    }

    public function testCallActivityWithError()
    {
        $this->withPersonalAccessClient();
        $child = $this->createProcess([
            'id' => 4,
            'bpmn' => file_get_contents(__DIR__ . '/processes/SubProcessWithError.bpmn'),
        ]);
        $parentBpmn = file_get_contents(__DIR__ . '/processes/ParentCallActivityBoundaryError.bpmn');
        $parent = $this->createProcess([
            'id' => 5,
            'bpmn' => $parentBpmn,
        ]);

        // Start a process request
        $request = $this->startProcess($parent, 'node_1');

        // Catch SubProcess is closed by boundary event
        $childRequest = ProcessRequest::get()[1];
        $this->assertEquals('COMPLETED', $childRequest->status);

        // Error is catch by boundary event and continue to task "Error Catch"
        $request->refresh();
        $activeTokens = $request->tokens()->where('status', 'ACTIVE')->get();
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Error Catch', $activeTokens[0]->element_name);
    }
}

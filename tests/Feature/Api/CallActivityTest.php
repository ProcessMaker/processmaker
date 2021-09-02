<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ScriptExecutor;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use ProcessMaker\Models\ProcessRequestToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Testing\File;

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
        ScriptExecutor::setTestConfig('php');

        // Script task requires passport installed (oauth token)
        Artisan::call('passport:install',['-vvv' => true]);
        
        // Create the processes
        $child = $this->createProcess([
            'id' => 2,
            'bpmn' => file_get_contents(__DIR__ . '/processes/child.bpmn')
        ]);

        $parent = $this->createProcess([
            'id' => 1,
            'bpmn' => str_replace(
                ['[child_id]','[start_event_id]'],
                [$child->id, 'node_8'],
                file_get_contents(__DIR__ . '/processes/parent.bpmn')
            )
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
        $this->assertEquals('COMPLETED', $instance->status);
        $this->assertEquals('COMPLETED', $subInstance->status);
    }

    public function testCallActivityFiles()
    {
        // Create the processes
        $child = $this->createProcess([
            'id' => 2,
            'bpmn' => file_get_contents(__DIR__ . '/processes/child-files.bpmn')
        ]);
        $parent = $this->createProcess([
            'id' => 1,
            'bpmn' => str_replace(
                ['[child_id]','[start_event_id]'],
                [$child->id, 'startevent'],
                file_get_contents(__DIR__ . '/processes/parent-files.bpmn')
            )
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
            'data_name' => 'child_photo'
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
}

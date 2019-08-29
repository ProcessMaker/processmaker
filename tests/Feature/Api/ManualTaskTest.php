<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Testing\File;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use ProcessMaker\Providers\WorkflowServiceProvider;

class ManualTaskTest extends TestCase
{
    use RequestHelper;
    use TestProcessExecutionTrait;

    /**
     * Test a user that participate from the request can
     * upload a file.
     */
    public function testUploadRequestFile()
    {
        $this->loadTestProcess(
            file_get_contents(__DIR__ . '/processes/FileUpload.bpmn'),
            [
                '2' => factory(User::class)->create([
                    'status' => 'ACTIVE',
                    'is_administrator' => false,
                ])
            ]
        );

        // Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        $requestJson = $response->json();
        $request = ProcessRequest::find($requestJson['id']);

        // Upload file from the first task
        $uploadTask = $request->tokens()->where('status', 'ACTIVE')->first();
        $route = route('api.requests.files.store', [$request->id, 'event' => 'node_1']);
        $response = $this->actingAs($uploadTask->user, 'api')
                         ->json('POST', $route, [
                             'file' => File::image('photo.jpg')
                         ]);
        // Check the user has access to upload a file
        $response->assertStatus(200);
        // Check the file was uploaded
        $this->assertEquals($request->getMedia()[0]->file_name, 'photo.jpg');
    }
}

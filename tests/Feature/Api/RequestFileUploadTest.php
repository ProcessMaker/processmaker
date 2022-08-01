<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Testing\File;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class RequestFileUploadTest extends TestCase
{
    use RequestHelper;
    use TestProcessExecutionTrait;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @var \ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface
     */
    protected $task;

    /**
     * @var \ProcessMaker\Models\User[]
     */
    protected $assigned = [];

    /**
     * Test a user that participate from the request can
     * upload a file.
     */
    public function testUploadRequestFile()
    {
        $this->loadTestProcess(
            file_get_contents(__DIR__.'/processes/FileUpload.bpmn'),
            [
                '2' => factory(User::class)->create([
                    'status' => 'ACTIVE',
                    'is_administrator' => false,
                ]),
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
                'file' => File::image('photo.jpg'),
                'data_name' => 'photo',
            ]);
        // Check the user has access to upload a file
        $response->assertStatus(200);
        // Check the file was uploaded
        $this->assertEquals($request->getMedia()[0]->file_name, 'photo.jpg');
    }

    /**
     * Test a user that does not participate from the request can
     * not upload a file.
     */
    public function testCanNotUploadRequestFile()
    {
        // Load the FileUpload.bpmn process
        $this->loadTestProcess(
            file_get_contents(__DIR__.'/processes/FileUpload.bpmn'),
            [
                '2' => factory(User::class)->create([
                    'status' => 'ACTIVE',
                    'is_administrator' => false,
                ]),
            ]
        );

        // Create an external user
        $doesNotParticipateUser = factory(User::class)->create([
            'status' => 'ACTIVE',
            'is_administrator' => false,
        ]);

        // Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        $requestJson = $response->json();
        $request = ProcessRequest::find($requestJson['id']);

        // Upload file with a user that does not participate in the request
        $route = route('api.requests.files.store', [$request->id, 'event' => 'node_1']);
        $response = $this->actingAs($doesNotParticipateUser, 'api')
            ->json('POST', $route, [
                'file' => File::image('photo.jpg'),
            ]);
        // Check the user does not have access to upload a file
        $response->assertStatus(403);
        // Check the file was not uploaded
        $this->assertEquals(0, $request->getMedia()->count());
    }

    /**
     * Test a user that can claim a self task can view the requests uploaded files.
     */
    public function testViewUploadedRequestFile()
    {
        $this->loadTestProcess(
            file_get_contents(__DIR__.'/processes/ViewFileUpload.bpmn'),
            [
                '2' => factory(User::class)->create([
                    'status' => 'ACTIVE',
                    'is_administrator' => false,
                ]),
            ]
        );
        // Create the user assigned to the task (as self service)
        $selfServiceUser = factory(User::class)->create([
            'id' => 15,
            'status' => 'ACTIVE',
            'is_administrator' => false,
        ]);
        $anotherUser = factory(User::class)->create([
            'status' => 'ACTIVE',
            'is_administrator' => false,
        ]);

        // Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        $requestJson = $response->json();
        $request = ProcessRequest::find($requestJson['id']);

        // Upload a file to the request
        $route = route('api.requests.files.store', [$request->id, 'event' => 'node_1']);
        $response = $this->actingAs($request->user, 'api')
            ->json('POST', $route, [
                'file' => File::image('photo.jpg'),
                'data_name' => 'photo',
            ]);

        // User assigned to the self service task can view the file
        $route = route('api.requests.files.index', [$request->id, 'name' => 'photo']);
        $response = $this->actingAs($selfServiceUser, 'api')
            ->json('GET', $route);
        $response->assertStatus(200);

        // User NOT assigned to the self service task can not view the file
        $route = route('api.requests.files.index', [$request->id, 'name' => 'photo']);
        $response = $this->actingAs($anotherUser, 'api')
            ->json('GET', $route);
        $response->assertStatus(403);
    }
}

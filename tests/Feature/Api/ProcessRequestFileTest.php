<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessRequestFileTest extends TestCase
{
    use RequestHelper;

    /**
     * test process request files index
     */
    public function testIndex()
    {
        //create a request
        $process_request = ProcessRequest::factory()->create();

        //upload a fake document with id of the request
        $fileUpload = File::image('photo.jpg');

        //save the file with media lib
        $process_request
            ->addMedia($fileUpload)
            ->withCustomProperties(['data_name' => 'test'])
            ->toMediaCollection();

        $response = $this->apiCall('GET', '/requests/' . $process_request->id . '/files');
        $response->assertStatus(200);
        $this->assertEquals($response->json()['data'][0]['file_name'], 'photo.jpg');
    }

    /**
     * Test file upload associated with a process request id
     */
    public function testFileUploadWithProcessRequestID()
    {
        //create a request
        $process_request = ProcessRequest::factory()->create();

        //post photo id with the request
        $response = $this->apiCall('POST', '/requests/' . $process_request->id . '/files', [
            'file' => File::image('photo.jpg'),
            'data_name' => 'photo',
        ]);
        $response->assertStatus(200);
        $this->assertEquals($process_request->getMedia()[0]->file_name, 'photo.jpg');
    }

    /**
     * test delete of Media attached to a request
     */
    public function testDeleteFile()
    {
        //create a request
        $process_request = ProcessRequest::factory()->create();
        // upload file
        $fileUpload = File::image('HEEEEy.jpg');
        $process_request
            ->addMedia($fileUpload)
            ->withCustomProperties(['data_name' => 'test'])
            ->toMediaCollection();
        //delete the file
        $process_request->refresh();
        $process_request->getMedia()[0]->delete();
        $process_request->refresh();
        //confirm the file was deleted
        $this->assertEquals($process_request->getMedia()->count(), 0);
    }

    /**
     * test get a single file for a process by id
     */
    public function testShow()
    {
        //create a request
        $process_request = ProcessRequest::factory()->create();

        //upload a fake document with id of the request
        $fileUpload1 = File::image('photo1.jpg');
        $fileUpload2 = File::image('photo2.jpg');

        //save the file with media lib
        $file1 = $process_request
            ->addMedia($fileUpload1)
            ->withCustomProperties(['data_name' => 'test'])
            ->toMediaCollection();
        $file2 = $process_request
            ->addMedia($fileUpload2)
            ->withCustomProperties(['data_name' => 'test'])
            ->toMediaCollection();

        $response = $this->apiCall('GET', '/requests/' . $process_request->id . '/files/' . $file2->id);
        $response->assertStatus(200);
        $this->assertEquals(
            'attachment; filename=photo2.jpg',
            $response->headers->get('content-disposition')
        );
    }
}

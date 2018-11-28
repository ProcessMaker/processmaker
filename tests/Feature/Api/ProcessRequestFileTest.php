<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\ProcessRequest;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\User;
use Illuminate\Http\Testing\File;


class ProcessRequestFileTest extends TestCase
{
    use RequestHelper;
    /**
     * test process request files index
     */
    public function testIndex()
    {
        //create a request
        $process_request = factory(ProcessRequest::class)->create();

        //upload a fake document with id of the request
        $fileUpload = File::image('photo.jpg');

        //save the file with media lib
        $process_request->addMedia($fileUpload)->toMediaCollection();

        $response = $this->apiCall('GET', '/requests/' . $process_request->id . '/files');
        $response->assertStatus(200);
        $this->assertEquals($response->json()['data'][0]['file_name'], 'photo.jpg');
    }

    
    /**
     * Test file upload associated with a process request id
     *
     * 
     */ 
    public function testFileUploadWithProcessRequestID()
    {
        //create a request
        $process_request = factory(ProcessRequest::class)->create();

        //post photo id with the request 
        $response = $this->apiCall('POST', '/requests/' . $process_request->id . '/files', [
            'file' => File::image('photo.jpg')
        ]);
        $response->assertStatus(200);
        $this->assertEquals($process_request->getMedia()[0]->file_name, 'photo.jpg');

    }

    /**
     * test update of an existing file 
     */
    public function testFileUpdate()
    {
        //create a request
        $process_request = factory(ProcessRequest::class)->create();
        // upload file 
        $fileUpload = File::image('photo.jpg');

        $fileUploadUpdate= File::image('updatedFile.jpg');
        //save the file with media lib
        $process_request->addMedia($fileUpload)->toMediaCollection();
        //update
        $file = $process_request->getMedia()[0]->id;

        $response = $this->apiCall('PUT', '/requests/' . $process_request->id . '/files/' . $file, [
            'file' => $fileUploadUpdate
        ]);
        $process_request->refresh();
        $response->assertStatus(200);

        $this->assertEquals($process_request->getMedia()[0]->file_name, 'updatedFile.jpg');
    }

    /**
     * test delete of Media attached to a request 
     */
    public function testDeleteFile() 
    {
        //create a request
        $process_request = factory(ProcessRequest::class)->create();
        // upload file 
        $fileUpload = File::image('HEEEEy.jpg');
        $process_request->addMedia($fileUpload)->toMediaCollection();
        //delete the file
        $process_request->refresh();
        $process_request->getMedia()[0]->delete();
        $process_request->refresh();
        //confirm the file was deleted
        $this->assertEquals($process_request->getMedia()->count(),0);
    }
}

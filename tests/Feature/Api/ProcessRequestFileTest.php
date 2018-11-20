<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\ProcessRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Hash;


class ProcessRequestFileTest extends TestCase
{
    use RequestHelper;
    /**
     * 
     */
    public function testIndex()
    {
        //create a request
        $process_request = factory(ProcessRequest::class)->create();

        //upload a fake document with id of the request
        $fileUpload = UploadedFile::fake()->create('my_test_file123.pdf');

        //save the file with media lib
        $process_request->addMedia($fileUpload)->toMediaCollection();
        $response = $this->apiCall('GET', '/requests/' . $process_request->id . '/files');
        dd($response->json());
        $response->assertStatus(200);
    }

    
    /**
     * Test file upload associated with a process request id
     *
     * 
     */ 
    // public function testFileUploadWithProcessRequestID()
    // {
    //     //create a request
    //     $process_request = factory(ProcessRequest::class)->create();

    //     //upload a fake document with id of the request
    //     $fileUpload = UploadedFile::fake()->create('my_test_file123.pdf');

    //     //save the file with media lib
    //     $process_request->addMedia($fileUpload)->toMediaCollection('local');
    //     //post photo id with the request 
    //     $response = $this->apiCall('POST', '/request' . $process_request->id . '/files', [
    //         'file' => $fileUpload
    //     ]);

    //     $response->assertStatus(200);
    //     $response = $this->apiCall('GET', '/request' . $process_request->id . '/files');
    //     $response->assertStatus(200);
    // }
}

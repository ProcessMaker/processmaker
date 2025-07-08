<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class MediaConfigTest extends TestCase
{
    use RequestHelper;

    public function testMediaMaxFileSize()
    {
        // Set a small max file size for testing (1MB)
        Config::set('media-library.max_file_size', 1024 * 1024); // 1MB

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $user = $processRequest->user;

        // Ensure storage disk is available
        Storage::fake('local');

        // Test file within size limit (500KB)
        $validFilePath = storage_path('app/test_valid.txt');
        file_put_contents($validFilePath, str_repeat('a', 500 * 1024)); // 500KB

        $validFile = new UploadedFile(
            $validFilePath,
            'valid.txt',
            null,
            null,
            true // Test mode
        );

        $response = $this->actingAs($user)->apiCall('POST', '/requests/' . $processRequest->id . '/files', [
            'file' => $validFile,
            'data_name' => 'test_file',
        ]);
        $response->assertStatus(200);
        $this->assertEquals(1, $processRequest->fresh()->getMedia()->count());

        // Test file exceeding size limit (2MB)
        $oversizedFilePath = storage_path('app/test_oversized.txt');
        file_put_contents($oversizedFilePath, str_repeat('a', 2048 * 1024)); // 2MB

        $oversizedFile = new UploadedFile(
            $oversizedFilePath,
            'oversized.txt',
            null,
            null,
            true
        );

        $response = $this->apiCall('POST', '/requests/' . $processRequest->id . '/files', [
            'file' => $oversizedFile,
            'data_name' => 'test_file_2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);

        // Verify the error message mentions file size
        $this->assertStringContainsString(
            'file may not be greater than 1024 kilobytes',
            $response->json()['errors']['file'][0]
        );

        // Verify no new media was added
        $this->assertEquals(1, $processRequest->fresh()->getMedia()->count());
    }

    public function testMediaMaxFileSizeFromEnv()
    {
        // Test that the config reads from environment variable
        $maxSize = 5 * 1024 * 1024; // 5MB
        Config::set('media-library.max_file_size', $maxSize);

        $this->assertEquals(
            $maxSize,
            config('media-library.max_file_size'),
            'Media max file size should match environment configuration'
        );
    }
}

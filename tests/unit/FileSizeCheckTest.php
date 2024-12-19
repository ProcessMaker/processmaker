<?php

namespace Tests\Unit\Middleware;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Middleware\FileSizeCheck;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class FileSizeCheckTest extends TestCase
{
    use RequestHelper;

    private string $response = 'OK';

    private const TEST_ROUTE = '/upload';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(FileSizeCheck::class)->any(self::TEST_ROUTE, function () {
            return response()->json(['message' => $this->response], 200);
        });

        $this->user = User::factory()->create([
            'password' => bcrypt('password'),
            'is_administrator' => true,
        ]);
    }

    public function testNoFilesPassesThrough()
    {
        $response = $this->postJson(self::TEST_ROUTE);

        $response->assertStatus(200);
        $response->assertJson(['message' => $this->response]);
    }

    public function testValidFileUpload()
    {
        ini_set('upload_max_filesize', '2M');

        $file = UploadedFile::fake()->create('test.pdf', 500); // 500 KB
        $response = $this->postJson(self::TEST_ROUTE, [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => $this->response]);
    }

    public function testLargeFileRejected()
    {
        ini_set('upload_max_filesize', '2M'); // 2 MB.

        $file = UploadedFile::fake()->create('test.pdf', 4000); // 4 MB.
        $response = $this->postJson(self::TEST_ROUTE, [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The file is too large. Maximum allowed size is 2M',
        ]);
    }

    public function testInvalidFileUpload()
    {
        ini_set('upload_max_filesize', '2M');

        // Mock of an invalid file using PHPUnit.
        $mockFile = $this->createMock(UploadedFile::class);
        $mockFile->method('isValid')->willReturn(false); // Simulate invalid file.
        $mockFile->method('getSize')->willReturn(500); // Simulate file size.
        $mockFile->method('getClientOriginalName')->willReturn('test.pdf');

        // Act.
        $response = $this->postJson(self::TEST_ROUTE, [
            'file' => $mockFile,
        ]);

        // Assert.
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The file upload was not successful.',
        ]);
    }

    public function testTotalSizeExceedsLimit()
    {
        ini_set('upload_max_filesize', '2M'); // 2 MB.

        $file1 = UploadedFile::fake()->create('file1.pdf', 1000); // 1 MB.
        $file2 = UploadedFile::fake()->create('file2.pdf', 1500); // 1.5 MB.
        $totalSize = $file1->getSize() + $file2->getSize();

        $response = $this->postJson(self::TEST_ROUTE, [
            'file1' => $file1,
            'file2' => $file2,
            'totalSize' => $totalSize, // 2.5 MB (exceeds limit).
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The file is too large. Maximum allowed size is 2M',
        ]);
    }

    public function testTotalSizeWithinLimit()
    {
        ini_set('upload_max_filesize', '5M'); // 5 MB

        $file1 = UploadedFile::fake()->create('file1.pdf', 2000); // 2 MB
        $file2 = UploadedFile::fake()->create('file2.pdf', 1000); // 1 MB

        $response = $this->postJson(self::TEST_ROUTE, [
            'file1' => $file1,
            'file2' => $file2,
            'totalSize' => 3000, // 3 MB
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => $this->response]);
    }

    /**
     * Test if the middleware is applied to API routes.
     */
    public function testFileSizeCheckMiddlewareIsAppliedToApiRoutes()
    {
        $processRequest = ProcessRequest::factory()->create();
        $response = $this->apiCall(
            'POST',
            route('api.requests.files.store', [$processRequest->id]),
            ['file' => UploadedFile::fake()->create('test.pdf', 500)]
        );

        // Verify that the header added by the middleware is present.
        $response->assertOk();
        $response->assertHeader('X-FileSize-Checked', 'true');
    }

    /**
     * Test if the middleware is applied to Web routes.
     */
    public function testFileSizeCheckMiddlewareIsAppliedToWebRoutes()
    {
        $response = $this->webCall('GET', route('processes.index'));

        $response->assertOk();
        $response->assertHeader('X-FileSize-Checked', 'true');
    }

    /**
     * Test if the middleware is applied to package routes.
     */
    public function testFileSizeCheckMiddlewareIsAppliedToPackageRoutes()
    {
        $hasPackage = \hasPackage('package-files');
        if (!$hasPackage) {
            $this->markTestSkipped('The package is not installed.');
        }

        \ProcessMaker\Package\Files\AddPublicFilesProcess::call();
        $response = $this->apiCall(
            'POST',
            route('api.file-manager.store'),
            ['file' => UploadedFile::fake()->create('test.pdf', 500)]
        );

        $response->assertOk();
        $response->assertHeader('X-FileSize-Checked', 'true');
    }
}

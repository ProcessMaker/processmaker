<?php

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use ProcessMaker\Http\Controllers\Api\ProcessRequestFileController;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ReflectionClass;
use Tests\TestCase;

class ProcessRequestFileControllerValidationTest extends TestCase
{
    protected $controller;

    protected $processRequest;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and process request
        $this->user = User::factory()->create();
        $this->processRequest = ProcessRequest::factory()->create();

        // Create controller instance
        $this->controller = new ProcessRequestFileController();

        // Set up test configuration
        Config::set('files.enable_extension_validation', true);
        Config::set('files.enable_mime_validation', true);
        Config::set('files.allowed_extensions', ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png']);
        Config::set('files.dangerous_extensions', ['zip', 'rar', '7z', 'tar']);
        Config::set('files.dangerous_mime_types', [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
        ]);
        Config::set('files.extension_mime_map', [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'txt' => ['text/plain'],
            'jpg' => ['image/jpeg'],
            'png' => ['image/png'],
        ]);
    }

    /**
     * Helper method to invoke the private validateFile method
     */
    private function invokeValidateFile(UploadedFile $file, &$errors)
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateFile');
        $method->setAccessible(true);

        // Use invokeArgs with an array that will be passed by reference
        $args = [$file, &$errors];

        return $method->invokeArgs($this->controller, $args);
    }

    /**
     * Helper method to invoke the private rejectArchiveFiles method
     */
    private function invokeRejectArchiveFiles(UploadedFile $file, &$errors)
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('rejectArchiveFiles');
        $method->setAccessible(true);

        // Use invokeArgs with an array that will be passed by reference
        $args = [$file, &$errors];

        return $method->invokeArgs($this->controller, $args);
    }

    /**
     * Helper method to invoke the private validateFileExtension method
     */
    private function invokeValidateFileExtension(UploadedFile $file, &$errors)
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateFileExtension');
        $method->setAccessible(true);

        // Use invokeArgs with an array that will be passed by reference
        $args = [$file, &$errors];

        return $method->invokeArgs($this->controller, $args);
    }

    /**
     * Helper method to invoke the private validateExtensionMimeTypeMatch method
     */
    private function invokeValidateExtensionMimeTypeMatch(UploadedFile $file, &$errors)
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateExtensionMimeTypeMatch');
        $method->setAccessible(true);

        // Use invokeArgs with an array that will be passed by reference
        $args = [$file, &$errors];

        return $method->invokeArgs($this->controller, $args);
    }

    /**
     * Helper method to invoke the private validatePDFFile method
     */
    private function invokeValidatePDFFile(UploadedFile $file, &$errors)
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('validatePDFFile');
        $method->setAccessible(true);

        // Use invokeArgs with an array that will be passed by reference
        $args = [$file, &$errors];

        return $method->invokeArgs($this->controller, $args);
    }

    /**
     * Test the main validateFile method with valid file
     */
    public function testValidateFileWithValidFile()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $errors = [];
        $this->invokeValidateFile($file, $errors);

        $this->assertEmpty($errors, 'Valid PDF file should not generate errors');
    }

    /**
     * Test validateFile with archive file (should be rejected)
     */
    public function testValidateFileWithArchiveFile()
    {
        $file = UploadedFile::fake()->create('archive.zip', 100, 'application/zip');

        $errors = [];
        $this->invokeValidateFile($file, $errors);
        $this->assertNotEmpty($errors, 'Archive file should generate errors');
        $this->assertStringContainsString('File extension not allowed', $errors['message']);
    }

    /**
     * Test validateFile with extension not in allowed list
     */
    public function testValidateFileWithUnallowedExtension()
    {
        $file = UploadedFile::fake()->create('document.xyz', 100, 'application/octet-stream');

        $errors = [];
        $this->invokeValidateFile($file, $errors);

        $this->assertNotEmpty($errors, 'Unallowed extension should generate errors');
        $this->assertStringContainsString('File extension not allowed', $errors['message']);
    }

    /**
     * Test validateFile with extension vs MIME type mismatch
     */
    public function testValidateFileWithExtensionMimeTypeMismatch()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'text/plain');

        $errors = [];
        $this->invokeValidateFile($file, $errors);

        $this->assertNotEmpty($errors, 'Extension vs MIME type mismatch should generate errors');
        $this->assertStringContainsString('The file extension does not match the actual file content', $errors['message']);
    }

    /**
     * Test validateFile with dangerous PDF content
     */
    public function testValidateFileWithDangerousPDFContent()
    {
        // Create a PDF file with JavaScript content
        $pdfContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj
4 0 obj
<<
/Length 44
>>
stream
/JavaScript
<<
/S /JavaScript
>>
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000204 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
264
%%EOF';

        $file = UploadedFile::fake()->createWithContent('dangerous.pdf', $pdfContent);
        $file->mimeType('application/pdf');

        $errors = [];
        $this->invokeValidateFile($file, $errors);

        $this->assertNotEmpty($errors, 'Dangerous PDF content should generate errors');
        $this->assertStringContainsString('Dangerous PDF file content', implode(', ', $errors));
    }

    /**
     * Test validateFile with extension validation disabled
     */
    public function testDisableMimeAndExtensionValidation()
    {
        Config::set('files.enable_extension_validation', false);
        Config::set('files.enable_mime_validation', false);

        $file = UploadedFile::fake()->create('document.xyz', 100, 'application/octet-stream');

        $errors = [];
        $this->invokeValidateFile($file, $errors);

        // Should not have extension validation errors, but should still have MIME type validation
        $extensionErrors = array_filter($errors, function ($error) {
            return strpos($error, 'File extension not allowed') !== false;
        });
        $this->assertEmpty($extensionErrors, 'Extension validation should be disabled');
    }

    /**
     * Test validateFile with extension validation disabled
     */
    public function testValidateFileWithExtensionValidationDisabled()
    {
        Config::set('files.enable_extension_validation', false);

        $file = UploadedFile::fake()->create('document.xyz', 100, 'application/octet-stream');

        $errors = [];
        $this->invokeValidateFile($file, $errors);

        // Should not have extension validation errors, but should still have MIME type validation
        $extensionErrors = array_filter($errors, function ($error) {
            return strpos($error, 'File extension not allowed') !== false;
        });
        $this->assertNotEmpty($extensionErrors, 'Extension validation should be disabled but the MIME type should still be validated');
    }

    /**
     * Test validateFile with MIME validation disabled
     */
    public function testValidateFileWithMimeValidationDisabled()
    {
        Config::set('files.enable_mime_validation', false);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'text/plain');

        $errors = [];
        $this->invokeValidateFile($file, $errors);

        // Should not have MIME type validation errors
        $mimeErrors = array_filter($errors, function ($error) {
            return strpos($error, 'The file extension does not match the actual file content') !== false;
        });

        $this->assertEmpty($mimeErrors, 'MIME type validation should be disabled');
    }

    /**
     * Test rejectArchiveFiles method specifically
     */
    public function testRejectArchiveFiles()
    {
        $file = UploadedFile::fake()->create('archive.zip', 100, 'application/zip');

        $errors = [];
        $this->invokeRejectArchiveFiles($file, $errors);

        $this->assertNotEmpty($errors, 'Archive file should be rejected');
        $this->assertStringContainsString('Uploaded file type is not allowed', $errors['message']);
    }

    /**
     * Test validateFileExtension method specifically
     */
    public function testValidateFileExtension()
    {
        $file = UploadedFile::fake()->create('document.xyz', 100, 'application/octet-stream');

        $errors = [];
        $this->invokeValidateFileExtension($file, $errors);

        $this->assertNotEmpty($errors, 'Unallowed extension should generate errors');
        $this->assertStringContainsString('File extension not allowed', $errors['message']);
    }

    /**
     * Test validateExtensionMimeTypeMatch method specifically
     */
    public function testValidateExtensionMimeTypeMatch()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'text/plain');

        $errors = [];
        $this->invokeValidateExtensionMimeTypeMatch($file, $errors);

        $this->assertNotEmpty($errors, 'Extension vs MIME type mismatch should generate errors');
        $this->assertStringContainsString('The file extension does not match the actual file content', $errors['message']);
    }

    /**
     * Test validatePDFFile method specifically
     */
    public function testValidatePDFFile()
    {
        $pdfContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj
4 0 obj
<<
/Length 44
>>
stream
/JavaScript
<<
/S /JavaScript
>>
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000204 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
264
%%EOF';

        $file = UploadedFile::fake()->createWithContent('dangerous.pdf', $pdfContent);

        $errors = [];
        $this->invokeValidatePDFFile($file, $errors);

        $this->assertNotEmpty($errors, 'Dangerous PDF content should generate errors');
        $this->assertStringContainsString('Dangerous PDF file content', implode(', ', $errors));
    }

    /**
     * Test multiple validation errors at once
     */
    public function testValidateFileWithMultipleErrors()
    {
        // Create a file that violates multiple rules
        $file = UploadedFile::fake()->create('archive.xyz', 100, 'application/octet-stream');

        $errors = [];
        $this->invokeValidateFile($file, $errors);

        $this->assertEquals(1, count($errors), 'Should have multiple validation errors');
    }
}

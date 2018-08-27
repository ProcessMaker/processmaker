<?php

namespace Tests\Feature\Api\Designer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Facades\ProcessFileManager;
use ProcessMaker\Model\EmailEvent;
use ProcessMaker\Model\ProcessFile;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\User;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use DatabaseTransactions;

    const API_TEST_PROJECT = '/api/1.0/project/';

    /**
     * @covers \ProcessMaker\Http\Controllers\Api\Designer\FileManagerController
     */
    public function testAccessControl()
    {

      $this->markTestSkipped('Access control via permissions and roles removed');

        $user = factory(User::class)->create([
            'password' => Hash::make('password'),

        ]);


        // We need a project
        $process = factory(Process::class)->create();

        $response = $this->actingAs($user, 'api')->json('GET', self::API_TEST_PROJECT . $process->uid . '/file-manager?path=public');

        // We will first get a 403, because we are not a process administrator
        $response->assertStatus(403);

        //Assert store into a folder
        Storage::fake('public');
        $data = [
            'filename' => 'file.html',
            'path' => 'public/folder',
            'content' => 'document content',
        ];
        $response = $this->actingAs($user, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);

        $response->assertStatus(403);
        Storage::disk('public')->assertMissing($process->uid . '/folder/file.html');

        $processFile = factory(ProcessFile::class)->create();
        $prfUid = $processFile->uid;
        //Change content
        $newContent = 'new content';
        $data = [
            'content' => $newContent,
        ];
        $response = $this->actingAs($user, 'api')->json('PUT', self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid, $data);
        $response->assertStatus(403);

        $response = $this->actingAs($user, 'api')->json('DELETE', self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid);
        $response->assertStatus(403);
    }

    /**
     * Test get a list of the files in a project.
     *
     */
    public function testGetPublic()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),

        ]);

        // We need a project
        $process = factory(Process::class)->create();

        //Check root path contains default folders
        $response = $this->actingAs($admin, 'api')->json('GET', self::API_TEST_PROJECT . $process->uid . '/file-manager?path=');
        $response->assertStatus(200);
        $response->assertJsonFragment(
            [
                'name' => 'templates',
                'type' => 'folder',
                'path' => '/',
                'editable' => false
            ]
        );
        $response->assertJsonFragment(
            [
                'name' => 'public',
                'type' => 'folder',
                'path' => '/',
                'editable' => false
            ]
        );

        //Assert public response
        Storage::fake('public');
        $response = $this->actingAs($admin, 'api')->json('GET', self::API_TEST_PROJECT . $process->uid . '/file-manager?path=public');
        $response->assertStatus(200);
        $response->assertJson([]);

        //Assert template response
        Storage::fake('mailtemplates');
        $response = $this->actingAs($admin, 'api')->json('GET', self::API_TEST_PROJECT . $process->uid . '/file-manager?path=templates');
        $response->assertStatus(200);
        $response->assertJson([]);

        //Assert invalid drive path
        $response = $this->actingAs($admin, 'api')->json('GET', self::API_TEST_PROJECT . $process->uid . '/file-manager?path=INVALID_DRIVE_PATH');

        //Create a folder and file that are not registered in ProcessFiles.
        Storage::disk('public')->put($process->uid . '/other/file.txt', 'other file');

        //Test get folder that is not registered in ProcessFiles.
        $response = $this->actingAs($admin, 'api')->json('GET', self::API_TEST_PROJECT . $process->uid . '/file-manager?path=public');

        $response->assertStatus(200);
        $response->assertJsonFragment(
            [
                'name' => 'other',
                'type' => 'folder',
                'path' => 'public',
            ]
        );

        //Test get file that is not registered in ProcessFiles.
        $response = $this->actingAs($admin, 'api')->json('GET', self::API_TEST_PROJECT . $process->uid . '/file-manager?path=public/other&get_content=true');
        $response->assertStatus(200);
        $response->assertJsonFragment(
            [
                'uid' => '',
                'filename' => 'file.txt',
                'user_id' => 0,
                'update_user_id' => null,
                'path' => 'public/other/',
                'type' => 'file',
                'editable' => 'true',
                'content' => 'other file',
            ]
        );
    }

    /**
     * Test the creation of a file.
     *
     */
    public function testCreateFile()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),

        ]);
        $process = factory(Process::class)->create();

        //Assert store into a folder
        Storage::fake('public');
        $data = [
            'filename' => 'file.html',
            'path' => 'public/folder',
            'content' => 'document content',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        //Check expected status
        $response->assertStatus(201);
        $response->assertJsonStructure();
        $json = $response->json();
        $prfUid = $json['uid'];
        $processFile = ProcessFile::where('uid', $prfUid)->first();
        //Check if file was stored
        Storage::disk('public')->assertExists($process->uid . '/folder/file.html');
        //Check owner user
        $this->assertEquals($admin->id, $processFile->user_id);
        //Folder with a final slash
        $data = [
            'filename' => 'file1.html',
            'path' => 'public/folder_1/',
            'content' => 'document content',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists($process->uid . '/folder_1/file1.html');

        //Test filename is required validation
        $data = [
            'path' => 'public/folder_1/',
            'content' => 'document content',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.required', ['attribute' => 'filename']),
            $response->json()['error']['message']
        );

        //Test filemanager.filename_is_valid validation
        $data = [
            'filename' => ' ../other/name.txt',
            'path' => 'public/folder_1/',
            'content' => 'document content',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.filename.filemanager.filename_is_valid', ['attribute' => 'filename']),
            $response->json()['error']['message']
        );

        //Test filemanager.store_only_html_to_templates validation
        $data = [
            'filename' => 'I_am_not_html.txt',
            'path' => 'templates/folder_1/',
            'content' => 'document content',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.filename.filemanager.store_only_html_to_templates', ['attribute' => 'filename']),
            $response->json()['error']['message']
        );

        //Test filemanager.do_not_store_exe_in_public validation
        $data = [
            'filename' => 'executable.exe',
            'path' => 'templates/folder_1/',
            'content' => 'EXE_FILE',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.filename.filemanager.do_not_store_exe_in_public', ['attribute' => 'prf filename']),
            $response->json()['error']['message']
        );

        //Test filemanager.do_not_store_php_in_public validation
        Config::set(['app.disable_php_upload_execution' => 1]);
        $data = [
            'filename' => 'index.php',
            'path' => 'public/',
            'content' => 'EXE_FILE',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.filename.filemanager.do_not_store_php_in_public', ['attribute' => 'prf filename']),
            $response->json()['error']['message']
        );
    }

    /**
     * Test update a file.
     *
     */
    public function testUpdateFile()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),

        ]);
        $process = factory(Process::class)->create();

        //Upload a public test file
        Storage::fake('public');
        $data = [
            'filename' => 'file.html',
            'path' => 'public/folder',
            'content' => 'document content',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists($process->uid . '/folder/file.html');
        $processFile = $response->json();
        $prfUid = $processFile['uid'];

        //Change content
        $newContent = 'new content';
        $data = [
            'content' => $newContent,
        ];
        $response = $this->actingAs($admin, 'api')->json('PUT', self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid, $data);
        $response->assertStatus(200);
        $content = Storage::disk('public')->get($process->uid . '/folder/file.html');
        $this->assertEquals($content, $newContent);
    }

    /**
     * Test delete a file.
     */
    public function testDeleteFile()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),

        ]);
        $process = factory(Process::class)->create();

        //Upload a public test file
        Storage::fake('public');
        $data = [
            'filename' => 'file.html',
            'path' => 'public/folder',
            'content' => 'document content',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists($process->uid . '/folder/file.html');
        $processFile = $response->json();
        $prfUid = $processFile['uid'];

        //Delete file
        $response = $this->actingAs($admin, 'api')->json('DELETE', self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid);
        $response->assertStatus(200);
        Storage::disk('public')->assertMissing($process->uid . '/folder/file.html');

        //Delete file that is used as template
        $data = [
            'filename' => 'routing_template.html',
            'path' => 'templates/',
            'content' => 'document content',
        ];
        $response = ProcessFileManager::store($process, $admin, $data);
        $path = $response['path'] . $response['filename'];
        $prfUid = $response['uid'];
        $process->derivation_screen_template = 'routing_template.html';
        $process->save();
        $response = $this->actingAs($admin, 'api')->json('DELETE', self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid);
        $response->assertStatus(422);
        $this->assertEquals(
            __(
                'validation.custom.processFile.filemanager.file_is_not_used_as_routing_screen',
                ['path' => $path]
            ),
            $response->json()['error']['message']
        );

        //Delete file that is used as email template
        $data = [
            'filename' => 'email_template.html',
            'path' => 'templates/',
            'content' => 'email content',
        ];
        $response = ProcessFileManager::store($process, $admin, $data);
        $path = $response['path'] . $response['filename'];
        $prfUid = $response['uid'];
        /* @var $processFile ProcessFile */
        $processFile = ProcessFile::where('uid', $response['uid'])->firstOrFail();
        $emailEvent = factory(EmailEvent::class)->create([
            'process_id' => $process->id,
            'process_file_id' => ProcessFile::where('uid', $prfUid)->first()->id,
        ]);
        $process->save();
        $response = $this->actingAs($admin, 'api')->json('DELETE', self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid);
        $response->assertStatus(422);
        $this->assertEquals(
            __(
                'validation.custom.processFile.filemanager.file_is_not_used_at_email_events',
                ['path' => $path]
            ),
            $response->json()['error']['message']
        );
    }

    /**
     * Test delete a folder.
     *
     */
    public function testDeleteFolder()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),

        ]);
        $process = factory(Process::class)->create();

        //Upload a public test file
        Storage::fake('public');
        $data = [
            'filename' => 'file5.html',
            'path' => 'public/folder5',
            'content' => 'document content',
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager', $data);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists($process->uid . '/folder5/file5.html');
        $processFile = $response->json();
        $prfUid = $processFile['uid'];

        //Delete file
        $response = $this->actingAs($admin, 'api')->json('DELETE', self::API_TEST_PROJECT . $process->uid . '/file-manager/folder?path=public/folder5');
        $response->assertStatus(200);
        Storage::disk('public')->assertMissing($process->uid . '/folder5/file5.html');

        //Verify that the record was deleted.
        $processFileExists = ProcessFile::where('uid', $prfUid)->exists();
        $this->assertFalse($processFileExists);
    }

    /**
     * Test upload a document
     */
    public function testUploadDocument()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),

        ]);
        $process = factory(Process::class)->create();
        Storage::fake('public');

        $processFile = factory(ProcessFile::class)->create([
            'process_id' => $process->id,
            'path' => 'tests/storage/public/' . $process->uid . '/image.png',
            'drive' => 'public',
            'path_for_client' => 'image.png',
        ]);
        $prfUid = $processFile->uid;

        Storage::disk('public')->assertMissing($processFile->getPathInDisk());
        $file = UploadedFile::fake()->image('upload_file.png', 16, 16);
        $data = [
            'file' => $file,
        ];
        $response = $this->actingAs($admin, 'api')->json('POST', self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid . '/upload', $data);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists($processFile->getPathInDisk());
        $this->assertEquals('image/png', Storage::disk('public')->getMimeType($processFile->getPathInDisk()));
    }

    /**
     * Test to get one process file.
     */
    public function testShowProcessFile()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),

        ]);
        $process = factory(Process::class)->create();

        Storage::fake('public');

        $processFile = factory(ProcessFile::class)->create([
            'process_id' => $process->id,
        ]);
        $prfUid = $processFile->uid;
        $processFile->setContent('show content');

        $response = $this->actingAs($admin, 'api')->json('GET', self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid);
        $response->assertStatus(200);
    }

    /**
     * Test to download a process file.
     */
    public function testDownload()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),

        ]);
        $process = factory(Process::class)->create();

        Storage::fake('public');

        //Test a text file
        $processFile = factory(ProcessFile::class)->create([
            'process_id' => $process->id,
        ]);
        $prfUid = $processFile->uid;
        $content = 'show content';
        $processFile->setContent($content);
        $basename = basename($processFile->path);
        $response = $this->actingAs($admin, 'api')->get(self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid . '/download');
        $response->assertStatus(200);
        $this->assertEquals($basename, $response->getFile()->getBasename());
        $this->assertEquals(strlen($content), $response->getFile()->getSize());

        //Test a binary file
        $processFile = factory(ProcessFile::class)->create([
            'process_id' => $process->id,
            'path' => 'tests/storage/public/' . $process->uid . '/image.png',
            'drive' => 'public',
            'path_for_client' => 'image.png',
        ]);
        $file = UploadedFile::fake()->image('upload_file.png', 16, 16);
        ProcessFileManager::putUploadedFileIntoProcessFile($file, $processFile);
        $prfUid = $processFile->uid;
        $basename = basename($processFile->path);
        $response = $this->actingAs($admin, 'api')->get(self::API_TEST_PROJECT . $process->uid . '/file-manager/' . $prfUid . '/download');
        $response->assertStatus(200);
        $this->assertEquals($basename, $response->getFile()->getBasename());
        $this->assertEquals($file->getSize(), $response->getFile()->getSize());
        $response->assertHeader('content-type', 'image/png');
    }
}

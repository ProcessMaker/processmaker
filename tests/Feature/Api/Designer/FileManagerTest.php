<?php

namespace Tests\Feature\Api\Designer;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Facades\ProcessFileManager;
use ProcessMaker\Model\EmailEvent;
use ProcessMaker\Model\ProcessFile;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class FileManagerTest extends ApiTestCase
{

    const API_TEST_PROJECT = '/api/1.0/project/';

    /**
     * @covers \ProcessMaker\Http\Controllers\Api\Designer\FileManagerController
     */
    public function testAccessControl()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_OPERATOR)->first()->id
        ]);
        $this->auth($user->username, 'password');

        // We need a project
        $process = factory(Process::class)->create();

        $response = $this->api('GET', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager?path=public');

        // We will first get a 403, because we are not a process administrator
        $response->assertStatus(403);

        //Assert store into a folder
        Storage::fake('public');
        $data = [
            'prf_filename' => 'file.html',
            'prf_path' => 'public/folder',
            'prf_content' => 'document content',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);

        $response->assertStatus(403);
        Storage::disk('public')->assertMissing($process->PRO_UID . '/folder/file.html');

        $processFile = factory(ProcessFile::class)->create();
        $prfUid = $processFile->PRF_UID;
        //Change content
        $newContent = 'new content';
        $data = [
            'prf_content' => $newContent,
        ];
        $response = $this->api('PUT', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager/' . $prfUid, $data);
        $response->assertStatus(403);

        $response = $this->api('DELETE', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager/' . $prfUid);
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
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        // We need a project
        $process = factory(Process::class)->create();

        $this->auth($admin->username, 'password');

        //Check root path contains default folders
        $response = $this->api('GET', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager?path=');
        $response->assertStatus(200);
        $response->assertJsonFragment(
            [
                'name'     => "templates",
                'type'     => "folder",
                'path'     => "/",
                'editable' => false
            ]
        );
        $response->assertJsonFragment(
            [
                'name'     => "public",
                'type'     => "folder",
                'path'     => "/",
                'editable' => false
            ]
        );
        
        //Assert public response
        Storage::fake('public');
        $response = $this->api('GET', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager?path=public');
        $response->assertStatus(200);
        $response->assertJson([]);

        //Assert template response
        Storage::fake('mailtemplates');
        $response = $this->api('GET', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager?path=templates');
        $response->assertStatus(200);
        $response->assertJson([]);

        //Assert invalid drive path
        $response = $this->api('GET', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager?path=INVALID_DRIVE_PATH');

        //Create a folder and file that are not registered in ProcessFiles.
        Storage::disk('public')->put($process->PRO_UID . '/other/file.txt', 'other file');

        //Test get folder that is not registered in ProcessFiles.
        $response = $this->api('GET', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager?path=public');
        $response->assertStatus(200);
        $response->assertJsonFragment(
            [
                "prf_name" => "other",
                "prf_type" => "folder",
                "prf_path" => "public",
            ]
        );

        //Test get file that is not registered in ProcessFiles.
        $response = $this->api('GET', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager?path=public/other&get_content=true');
        $response->assertStatus(200);
        $response->assertJsonFragment(
            [
                "prf_uid"            => "",
                "prf_filename"       => "file.txt",
                "usr_uid"            => "",
                "prf_update_usr_uid" => "",
                "prf_path"           => "public/other/",
                "prf_type"           => "file",
                "prf_editable"       => "true",
                "prf_content"        => "other file",
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
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $process = factory(Process::class)->create();
        //
        $this->auth($admin->username, 'password');

        //Assert store into a folder
        Storage::fake('public');
        $data = [
            'prf_filename' => 'file.html',
            'prf_path' => 'public/folder',
            'prf_content' => 'document content',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        //Check expected status
        $response->assertStatus(201);
        $response->assertJsonStructure();
        $json = $response->json();
        $prfUid = $json['prf_uid'];
        $processFile = ProcessFile::where('PRF_UID', $prfUid)->first();
        //Check if file was stored
        Storage::disk('public')->assertExists($process->PRO_UID . '/folder/file.html');
        //Check owner user
        $this->assertEquals($admin->username, $processFile->user->username);
        //Folder with a final slash
        $data = [
            'prf_filename' => 'file1.html',
            'prf_path' => 'public/folder_1/',
            'prf_content' => 'document content',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists($process->PRO_UID . '/folder_1/file1.html');

        //Test prf_filename is required validation
        $data = [
            'prf_path' => 'public/folder_1/',
            'prf_content' => 'document content',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.required', ['attribute'=>'prf filename']),
            $response->json()['error']['message']
        );

        //Test filemanager.filename_is_valid validation
        $data = [
            'prf_filename' => ' ../other/name.txt',
            'prf_path' => 'public/folder_1/',
            'prf_content' => 'document content',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.prf_filename.filemanager.filename_is_valid', ['attribute'=>'prf filename']),
            $response->json()['error']['message']
        );

        //Test filemanager.store_only_html_to_templates validation
        $data = [
            'prf_filename' => 'I_am_not_html.txt',
            'prf_path' => 'templates/folder_1/',
            'prf_content' => 'document content',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.prf_filename.filemanager.store_only_html_to_templates', ['attribute'=>'prf filename']),
            $response->json()['error']['message']
        );

        //Test filemanager.do_not_store_exe_in_public validation
        $data = [
            'prf_filename' => 'executable.exe',
            'prf_path' => 'templates/folder_1/',
            'prf_content' => 'EXE_FILE',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.prf_filename.filemanager.do_not_store_exe_in_public', ['attribute'=>'prf filename']),
            $response->json()['error']['message']
        );

        //Test filemanager.do_not_store_php_in_public validation
        Config::set(['app.disable_php_upload_execution' => 1]);
        $data = [
            'prf_filename' => 'index.php',
            'prf_path' => 'public/',
            'prf_content' => 'EXE_FILE',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.custom.prf_filename.filemanager.do_not_store_php_in_public', ['attribute'=>'prf filename']),
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
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $process = factory(Process::class)->create();

        $this->auth($admin->username, 'password');

        //Upload a public test file
        Storage::fake('public');
        $data = [
            'prf_filename' => 'file.html',
            'prf_path' => 'public/folder',
            'prf_content' => 'document content',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists($process->PRO_UID . '/folder/file.html');
        $processFile = $response->json();
        $prfUid = $processFile['prf_uid'];

        //Change content
        $newContent = 'new content';
        $data = [
            'prf_content' => $newContent,
        ];
        $response = $this->api('PUT', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager/' . $prfUid, $data);
        $response->assertStatus(200);
        $content = Storage::disk('public')->get($process->PRO_UID . '/folder/file.html');
        $this->assertEquals($content, $newContent);
    }

    /**
     * Test delete a file.
     */
    public function testDeleteFile()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $process = factory(Process::class)->create();

        $this->auth($admin->username, 'password');

        //Upload a public test file
        Storage::fake('public');
        $data = [
            'prf_filename' => 'file.html',
            'prf_path' => 'public/folder',
            'prf_content' => 'document content',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists($process->PRO_UID . '/folder/file.html');
        $processFile = $response->json();
        $prfUid = $processFile['prf_uid'];

        //Delete file
        $response = $this->api('DELETE', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager/' . $prfUid);
        $response->assertStatus(200);
        Storage::disk('public')->assertMissing($process->PRO_UID . '/folder/file.html');

        //Delete file that is used as template
        $data = [
            'prf_filename' => 'routing_template.html',
            'prf_path' => 'templates/',
            'prf_content' => 'document content',
        ];
        $response = ProcessFileManager::store($process, $admin, $data);
        $path = $response['prf_path'].$response['prf_filename'];
        $prfUid = $response['prf_uid'];
        $process->PRO_DERIVATION_SCREEN_TPL = 'routing_template.html';
        $process->save();
        $response = $this->api('DELETE', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager/' . $prfUid);
        $response->assertStatus(422);
        $this->assertEquals(
            __(
                'validation.custom.processFile.filemanager.file_is_not_used_as_routing_screen',
                ['path'=>$path]
            ),
            $response->json()['error']['message']
        );

        //Delete file that is used as email template
        $data = [
            'prf_filename' => 'email_template.html',
            'prf_path' => 'templates/',
            'prf_content' => 'email content',
        ];
        $response = ProcessFileManager::store($process, $admin, $data);
        $path = $response['prf_path'].$response['prf_filename'];
        $prfUid = $response['prf_uid'];
        /* @var $processFile ProcessFile */
        $processFile = ProcessFile::where('PRF_UID', $response['prf_uid'])->firstOrFail();
        $emailEvent = factory(EmailEvent::class)->create([
            'PRO_ID' => $process->PRO_ID,
            'PRF_UID' => $prfUid,
        ]);
        $process->save();
        $response = $this->api('DELETE', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager/' . $prfUid);
        $response->assertStatus(422);
        $this->assertEquals(
            __(
                'validation.custom.processFile.filemanager.file_is_not_used_at_email_events',
                ['path'=>$path]
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
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $process = factory(Process::class)->create();

        $this->auth($admin->username, 'password');

        //Upload a public test file
        Storage::fake('public');
        $data = [
            'prf_filename' => 'file5.html',
            'prf_path' => 'public/folder5',
            'prf_content' => 'document content',
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager', $data);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists($process->PRO_UID . '/folder5/file5.html');
        $processFile = $response->json();
        $prfUid = $processFile['prf_uid'];

        //Delete file 
        $response = $this->api('DELETE', self::API_TEST_PROJECT . $process->PRO_UID . '/file-manager/folder?path=public/folder5');
        $response->assertStatus(200);
        Storage::disk('public')->assertMissing($process->PRO_UID . '/folder5/file5.html');
        Storage::disk('public')->assertMissing($process->PRO_UID . '/folder5');

        //Verify that the record was deleted.
        $processFileExists = ProcessFile::where('PRF_UID', $prfUid)->exists();
        $this->assertFalse($processFileExists);
    }

    /**
     * Test upload a document
     */
    public function testUploadDocument()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $process = factory(Process::class)->create();
        $this->auth($admin->username, 'password');

        Storage::fake('public');

        $processFile = factory(ProcessFile::class)->create([
            'PRO_UID' => $process->PRO_UID,
            'PRF_PATH' => 'tests/storage/public/'.$process->PRO_UID.'/image.png',
            'PRF_DRIVE' => 'public',
            'PRF_PATH_FOR_CLIENT' => 'image.png',
        ]);
        $prfUid = $processFile->PRF_UID;

        Storage::disk('public')->assertMissing($processFile->getPathInDisk());
        $file = UploadedFile::fake()->image('upload_file.png', 16, 16);
        $data = [
            'prf_file' => $file,
        ];
        $response = $this->api('POST', self::API_TEST_PROJECT . $process->PRO_UID  . '/file-manager/' . $prfUid . '/upload', $data);
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
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $process = factory(Process::class)->create();
        $this->auth($admin->username, 'password');

        Storage::fake('public');

        $processFile = factory(ProcessFile::class)->create([
            'PRO_UID' => $process->PRO_UID,
        ]);
        $prfUid = $processFile->PRF_UID;
        $processFile->setContent('show content');

        $response = $this->api('GET', self::API_TEST_PROJECT . $process->PRO_UID  . '/file-manager/' . $prfUid);
        $response->assertStatus(200);
    }

    /**
     * Test to download a process file.
     */
    public function testDownload()
    {
        $admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $process = factory(Process::class)->create();
        $this->auth($admin->username, 'password');

        Storage::fake('public');

        $headers = [
            'Authorization' => 'Bearer ' . $this->token
        ];

        //Test a text file
        $processFile = factory(ProcessFile::class)->create([
            'PRO_UID' => $process->PRO_UID,
        ]);
        $prfUid = $processFile->PRF_UID;
        $content = 'show content';
        $processFile->setContent($content);
        $basename = basename($processFile->PRF_PATH);
        $response = $this->get(self::API_TEST_PROJECT . $process->PRO_UID  . '/file-manager/' . $prfUid . '/download', $headers);
        $response->assertStatus(200);
        $this->assertEquals($basename, $response->getFile()->getBasename());
        $this->assertEquals(strlen($content), $response->getFile()->getSize());

        //Test a binary file
        $processFile = factory(ProcessFile::class)->create([
            'PRO_UID' => $process->PRO_UID,
            'PRF_PATH' => 'tests/storage/public/'.$process->PRO_UID.'/image.png',
            'PRF_DRIVE' => 'public',
            'PRF_PATH_FOR_CLIENT' => 'image.png',
        ]);
        $file = UploadedFile::fake()->image('upload_file.png', 16, 16);
        ProcessFileManager::putUploadedFileIntoProcessFile($file, $processFile);
        $prfUid = $processFile->PRF_UID;
        $basename = basename($processFile->PRF_PATH);
        $response = $this->get(self::API_TEST_PROJECT . $process->PRO_UID  . '/file-manager/' . $prfUid . '/download', $headers);
        $response->assertStatus(200);
        $this->assertEquals($basename, $response->getFile()->getBasename());
        $this->assertEquals($file->getSize(), $response->getFile()->getSize());
        $response->assertHeader('content-type', 'image/png');
    }
}

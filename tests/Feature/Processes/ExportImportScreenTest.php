<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\UploadedFile;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ExportImportScreenTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    protected function setUpExecutor()
    {
        ScriptExecutor::setTestConfig('php');
    }

    /**
     * Test to ensure we can export and import
     *
     * @return void
     */
    public function testExportImportProcess()
    {
        // Create an admin user
        $adminUser = factory(User::class)->create([
            'username' => 'admin',
            'is_administrator' => true,
        ]);

        $standardUser = factory(User::class)->create([
            'username' => 'standard',
            'is_administrator' => false,
        ]);

        // Seed the processes table.
        Artisan::call('db:seed', ['--class' => 'ProcessSeeder']);

        // Assert that our database has what it should and not what it shouldn't
        $this->assertDatabaseHas('screens', ['title' => 'Approve']);
        $this->assertDatabaseHas('screens', ['title' => 'Not available']);
        $this->assertDatabaseHas('screens', ['title' => 'Request Time Off']);
        $this->assertDatabaseHas('screens', ['title' => 'Validate']);
        $this->assertDatabaseMissing('screens', ['title' => 'Approve 2']);

        // Get the process we'll be testing on
        $screen = Screen::where('title', 'Approve')->first();

        // Test to ensure our standard user cannot export a screen
        $this->user = $standardUser;
        $response = $this->apiCall('POST', "/screens/{$screen->id}/export");
        $response->assertStatus(403);

        // Test to ensure our admin user can export a screen
        $this->user = $adminUser;
        $response = $this->apiCall('POST', "/screens/{$screen->id}/export");
        $response->assertStatus(200);
        $response->assertJsonStructure(['url']);

        // Test to ensure we can download the exported file
        $response = $this->webCall('GET', $response->json('url'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=Approve.json');

        // Get our file contents (we have to do it this way because of
        // Symfony's weird response API)
        ob_start();
        $content = $response->sendContent();
        $content = ob_get_clean();

        // Save the file contents and convert them to an UploadedFile
        $fileName = tempnam(sys_get_temp_dir(), 'exported');
        file_put_contents($fileName, $content);
        $file = new UploadedFile($fileName, 'approve.json', null, null, null, true);

        // Test to ensure our standard user cannot import a screen
        $this->user = $standardUser;
        $response = $this->apiCall('POST', '/screens/import', [
            'file' => $file,
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('screens', ['title' => 'Approve 2']);

        // Test to ensure our admin user can import a screen
        $this->user = $adminUser;
        $response = $this->apiCall('POST', '/screens/import', [
            'file' => $file,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('screens', ['title' => 'Approve']);
        $this->assertDatabaseHas('screens', ['title' => 'Not available']);
        $this->assertDatabaseHas('screens', ['title' => 'Request Time Off']);
        $this->assertDatabaseHas('screens', ['title' => 'Validate']);
        $this->assertDatabaseHas('screens', ['title' => 'Approve 2']);

        //Test with type file processmaker
        // Get the process we'll be testing on
        $process = Process::where('name', 'Leave Absence Request')->first();

        // Test to ensure our admin user can export a process
        $this->user = $adminUser;
        $response = $this->apiCall('POST', "/processes/{$process->id}/export");
        $response->assertStatus(200);
        $response->assertJsonStructure(['url']);

        // Download a type file: processmaker.
        $response = $this->webCall('GET', $response->json('url'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename="Leave Absence Request.json"');

        // Get our file contents (we have to do it this way because of
        // Symfony's weird response API)
        ob_start();
        $content = $response->sendContent();
        $content = ob_get_clean();

        // Save the file contents and convert them to an UploadedFile
        $fileName = tempnam(sys_get_temp_dir(), 'exported');
        file_put_contents($fileName, $content);
        $file = new UploadedFile($fileName, 'leave_absence_request.json', null, null, null, true);

        // Test to ensure our admin user can import a other file
        $this->user = $adminUser;
        $response = $this->apiCall('POST', '/screens/import', [
            'file' => $file,
        ]);
        $response->assertStatus(200);
        //Unable to import the screen.
        $this->assertFalse($response->json('status'));
    }

    public function testImportScreenWithWatchers()
    {
        // Load the file to test
        $fileName = __DIR__ . '/../../Fixtures/screen_with_watchers.json';

        $file = new UploadedFile($fileName, 'screen_with_watchers.json', null, null, null, true);

        // Test to ensure our admin user can import a other file
        //$this->user = $adminUser;
        $response = $this->apiCall('POST', '/screens/import', [
            'file' => $file,
        ]);
        $response->assertStatus(200);

        //Able to import the screen.
        $this->assertTrue($response->json('status')['screens']['success']);
    }
}

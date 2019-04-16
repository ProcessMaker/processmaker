<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Database\Seeder;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Permission;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Http\UploadedFile;

class ExportImportTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

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
        $this->assertDatabaseHas('processes', ['name' => 'Leave Absence Request']);
        $this->assertDatabaseHas('screens', ['title' => 'Request Time Off']);
        $this->assertDatabaseHas('scripts', ['title' => 'Get available days Script']);
        $this->assertDatabaseMissing('processes', ['name' => 'Leave Absence Request 2']);
        $this->assertDatabaseMissing('screens', ['title' => 'Request Time Off 2']);
        $this->assertDatabaseMissing('scripts', ['title' => 'Get available days Script 2']);

        // Get the process we'll be testing on
        $process = Process::where('name', 'Leave Absence Request')->first();

        // Test to ensure our standard user cannot export a process
        $this->user = $standardUser;
        $response = $this->apiCall('POST', "/processes/{$process->id}/export");
        $response->assertStatus(403);
        
        // Test to ensure our admin user can export a process
        $this->user = $adminUser;
        $response = $this->apiCall('POST', "/processes/{$process->id}/export");
        $response->assertStatus(200);
        $response->assertJsonStructure(['url']);
        
        // Test to ensure we can download the exported file
        $response = $this->webCall('GET', $response->json('url'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=leave_absence_request.spark');
        
        // Get our file contents (we have to do it this way because of
        // Symfony's weird response API)
        ob_start();
        $content = $response->sendContent();
        $content = ob_get_clean();
        
        // Save the file contents and convert them to an UploadedFile
        $fileName = tempnam(sys_get_temp_dir(), 'exported');
        file_put_contents($fileName, $content);
        $file = new UploadedFile($fileName, 'leave_absence_request.spark', null, null, null, true);
        
        // Test to ensure our standard user cannot import a process
        $this->user = $standardUser;
        $response = $this->apiCall('POST', "/processes/import", [
            'file' => $file,
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('processes', ['name' => 'Leave Absence Request 2']);
        
        // Test to ensure our admin user can import a process
        $this->user = $adminUser;
        $response = $this->apiCall('POST', "/processes/import", [
            'file' => $file,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('processes', ['name' => 'Leave Absence Request 2']);
        $this->assertDatabaseHas('screens', ['title' => 'Request Time Off 2']);
        $this->assertDatabaseHas('scripts', ['title' => 'Get available days Script 2']);
    }
}

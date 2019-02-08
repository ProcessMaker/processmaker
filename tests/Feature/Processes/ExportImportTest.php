<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Database\Seeder;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Permission;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Shared\RequestHelper;

class ExportImportTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();
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
        
        // Get the process we'll be testing on
        $process = Process::where('name', 'Leave Absence Request')->first();
        
        // Get the permission we'll be using
        $permission = Permission::byName('export-processes');

        // Test to ensure our standard user cannot export a process
        $this->user = $standardUser;
        $response = $this->apiCall('POST', "/processes/{$process->id}/export");
        $response->assertStatus(403);
        
        // Test to ensure our admin user can export a process
        $this->user = $adminUser;
        $response = $this->apiCall('POST', "/processes/{$process->id}/export");
        $response->assertStatus(200);
        $response->assertJsonStructure(['url']);
    }
}

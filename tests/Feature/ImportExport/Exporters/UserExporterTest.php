<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Facades\DB;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Exporters\UserExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class UserExporterTest extends TestCase
{
    use HelperTrait;
    use RequestHelper;

    public $withPermissions = true;

    public function test()
    {
        DB::beginTransaction();
        $user = User::factory()->create(['username' => 'testuser', 'email' => 'foo@bar.com']);
        $group = Group::factory()->create(['name' => 'test group']);
        $user->groups()->sync([$group->id]);

        $permissions = ['create-groups', 'create-scripts', 'edit-groups'];
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $user->permissions()->sync($permissionIds);

        $payload = $this->export($user, UserExporter::class);
        DB::rollBack(); // Delete all created items since DB::beginTransaction
        $this->assertEquals(0, User::where('username', 'testuser')->count());
        $this->import($payload);

        $user = User::where('username', 'testuser')->firstOrFail();
        // $this->assertEquals('test group', $user->groups->first()->name);
        $this->assertEquals($permissions, $user->permissions->pluck('name')->toArray());
    }

    public function testDoNotCopyAdmin()
    {
        DB::beginTransaction();
        $this->addGlobalSignalProcess();
        $bpmn = file_get_contents(base_path('tests/Feature/Api/bpmnPatterns/SimpleTaskProcess.bpmn'));
        $bpmn = str_replace('pm:screenRef="2"', 'pm:screenRef=""', $bpmn);
        $user = User::factory()->create(['username' => 'admin', 'email' => 'foo@bar.com']);
        $group = Group::factory()->create(['name' => 'test group']);
        $process = Process::factory()->create(['manager_id' => $user->id, 'name' => 'test process', 'bpmn' => $bpmn]);
        $user->groups()->sync([$group->id]);

        $payload = $this->export($process, ProcessExporter::class);
        $originalUserCount = User::count();

        // Import

        $options = new Options([
            $user->uuid => ['mode' => 'copy'],
            $group->uuid => ['mode' => 'copy'],
            $process->uuid => ['mode' => 'copy'],
        ]);
        $importer = new Importer($payload, $options);
        $importer->doImport();

        $newProcess = Process::where('name', 'test process 2')->firstOrFail();
        $user = User::where('username', 'admin')->firstOrFail();

        $this->assertEquals($originalUserCount, User::count());
        // Original user's groups was NOT modified
        $this->assertEquals(['test group'], $user->groups()->pluck('name')->toArray());

        // Test importing on new instance
        DB::rollBack(); // Delete all created items since DB::beginTransaction
    }
}

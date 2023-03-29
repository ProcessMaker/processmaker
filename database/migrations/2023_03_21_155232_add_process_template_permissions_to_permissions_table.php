<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Permission;

class AddProcessTemplatePermissionsToPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissions = [
            ['Create Process Templates', 'create-process-templates'],
            ['View Process Templates', 'view-process-templates'],
            ['Import Process Templates', 'import-process-templates'],
            ['Export Process Templates', 'export-process-templates'],
            ['Edit Process Templates', 'edit-process-templates'],
            ['Archive Process Templates', 'archive-process-templates'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'group' => 'Process Templates',
                'title' => $permission[0],
                'name' => $permission[1],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('group', 'Process Templates')->delete();
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Permission;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissions = [
            ['Create Process Translations', 'create-process-translations'],
            ['View Process Translations', 'view-process-translations'],
            ['Import Process Translations', 'import-process-translations'],
            ['Export Process Translations', 'export-process-translations'],
            ['Edit Process Translations', 'edit-process-translations'],
            ['Cancel Process Translations', 'cancel-process-translations'],
            ['Delete Process Translations', 'delete-process-translations'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'group' => 'Process Translations',
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
        Permission::where('group', 'Process Translations')->delete();
    }
};

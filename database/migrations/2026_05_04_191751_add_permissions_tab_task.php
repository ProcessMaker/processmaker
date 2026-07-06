<?php

use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\Permission;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            ['Overview', 'overview-tab-task'],
            ['Summary', 'summary-tab-task'],
            ['Completed', 'completed-tab-task'],
            ['Form', 'form-tab-task'],
            ['Files', 'files-tab-task'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'group' => 'Task tabs',
                'title' => $permission[0],
                'name' => $permission[1],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('group', 'Task tabs')->delete();
    }
};

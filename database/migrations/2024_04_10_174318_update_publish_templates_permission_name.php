<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Permission;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->updatePermissionTitle('publish-screen-templates', 'Share Screen Templates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->updatePermissionTitle('publish-screen-templates', 'Publish Screen Templates');
    }

    /**
     * Update permission title.
     *
     * @param string $permissionName
     * @param string $newTitle
     * @return void
     */
    private function updatePermissionTitle(string $permissionName, string $newTitle): void
    {
        $permission = Permission::where('name', $permissionName)->first();

        if (!$permission) {
            return; // Permission not found, nothing to update
        }

        $permission->update(['title' => $newTitle]);
    }
};

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
        Permission::updateOrCreate([
            'name' => 'publish-screen-templates',
        ], [
            'title' => 'Publish Screen Templates',
            'group' => 'Users',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'publish-screen-templates')->delete();
    }
};

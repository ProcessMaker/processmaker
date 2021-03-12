<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Permission;

class AddFieldsToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('name', 32)->after('config')->nullable();
            $table->string('helper', 255)->after('name')->nullable();
            $table->string('group', 32)->after('helper')->nullable();
            $table->string('format', 16)->after('helper')->default('array');
            $table->boolean('hidden')->after('format')->default(1);
            $table->boolean('readonly')->after('hidden')->default(0);
            $table->json('ui')->after('readonly')->nullable();
        });
        
        if (! Permission::where('name', 'view-settings')->first()) {
            factory(Permission::class)->create([
                'title' => 'View Settings',
                'name' => 'view-settings',
                'group' => 'Settings',
            ]);
        }
        
        if (! Permission::where('name', 'update-settings')->first()) {
            factory(Permission::class)->create([
                'title' => 'Update Settings',
                'name' => 'update-settings',
                'group' => 'Settings',
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
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['name','helper','group','format','hidden','readonly','ui']);
        });
        
        if ($permission = Permission::where('name', 'view-settings')->first()) {
            $permission->delete();
        }
        
        if ($permission = Permission::where('name', 'update-settings')->first()) {
            $permission->delete();
        }
    }
}

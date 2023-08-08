<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('security_logs', function (Blueprint $table) {
            $table->json('data')->nullable();
            $table->index('user_id');
        });

        if (!Permission::where('name', 'create-security-logs')->first()) {
            Permission::factory()->create([
                'title' => 'Create Security Logs',
                'name' => 'create-security-logs',
                'group' => 'Security Logs',
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
        Schema::table('security_logs', function (Blueprint $table) {
            $table->dropColumn('data');
            $table->dropIndex('security_logs_user_id_index');
        });

        if ($permission = Permission::where('name', 'create-security-logs')->first()) {
            $permission->delete();
        }
    }
};

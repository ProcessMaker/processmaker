<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\SecurityLog;

class CreateSecurityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new SecurityLog;
        Schema::connection($model->getConnectionName())->create('security_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event', 40)->index();
            $table->string('ip', 40)->index()->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamp('occurred_at');
        });
        
        if (! Permission::where('name', 'view-security-logs')->first()) {
            factory(Permission::class)->create([
                'title' => 'View Security Logs',
                'name' => 'view-security-logs',
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
        Schema::dropIfExists('security_logs');
        
        if ($permission = Permission::where('name', 'view-security-logs')->first()) {
            $permission->delete();
        }
    }
}

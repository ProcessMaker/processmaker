<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDelegationManagerUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $dbPlatform = DB::connection()->getDoctrineSchemaManager()->getDatabasePlatform();
        if (!$dbPlatform->hasDoctrineTypeMappingFor('enum')) {
            $dbPlatform->registerDoctrineTypeMapping('enum', 'string');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('delegation_user_id')->nullable();
            $table->unsignedInteger('manager_id')->nullable();
            $table->json('schedule')->nullable();
            $table->foreign('delegation_user_id')->references('id')->on('users');
            $table->foreign('manager_id')->references('id')->on('users');
            $table->string('status')->default('ACTIVE')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_delegation_user_id_foreign');
            $table->dropForeign('users_manager_id_foreign');
            $table->dropColumn([
                'delegation_user_id',
                'manager_id',
                'schedule',
            ]);
        });
    }
}

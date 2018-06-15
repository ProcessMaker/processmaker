<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission_role', function(Blueprint $table)
        {
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('permission_id');
            $table->primary(['role_id','permission_id']);

            // setup relationship for Rol we belong to
            $table->foreign('role_id')->references('id')->on('roles')->ondelete('cascade');
            // setup relationship for Permission we belong to
            $table->foreign('permission_id')->references('id')->on('permissions')->ondelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission_role');
    }
}

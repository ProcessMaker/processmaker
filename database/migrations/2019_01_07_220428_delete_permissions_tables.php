<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeletePermissionsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('process_permissions');
        Schema::drop('permission_assignments');
        Schema::drop('permissions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->enum('type', ['ROUTE', 'RESOURCE']);
            $table->string('guard_name')->unique();
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('permission_assignments', function (Blueprint $table) {
          $table->increments('id');
          $table->unsignedInteger('permission_id');
          $table->morphs('assignable');
          $table->timestamps();

          $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        Schema::create('process_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('process_id');
            $table->unsignedInteger('permission_id');
            $table->morphs('assignable');
            $table->timestamps();

            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
        });
    }
}

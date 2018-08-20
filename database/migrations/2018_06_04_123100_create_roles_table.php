<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->unsignedInteger('parent_role_id')->nullable();
            $table->string('name');
            $table->string('description');
            $table->timestamps();
            $table->enum('status', ['ACTIVE', 'DISABLED'])->default('ACTIVE');

            // setup relationship for Parent rol we belong to
            $table->foreign('parent_role_id')->references('id')->on('roles')->ondelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
}

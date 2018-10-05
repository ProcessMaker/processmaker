<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission_assignments', function (Blueprint $table) {
          $table->uuid('uuid');
          $table->primary('uuid');
          $table->uuid('permission_uuid');
          $table->uuid('assignment_uuid');
          $table->enum('assignment_type',['user','group']);
          $table->timestamps();

          $table->foreign('permission_uuid')->references('uuid')->on('permissions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission_assignments');
    }
}

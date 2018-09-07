<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessTaskAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_task_assignments', function (Blueprint $table) {
            // columns
            $table->uuid('uuid');
            $table->uuid('process_task_uuid');
            $table->uuid('assignment_uuid');
            $table->enum('assignment_type', ['USER', 'GROUP', 'EXPRESSION']);
            $table->timestamps();

            // indexes
            $table->primary('uuid');

            // foreign keys
            $table->foreign('process_task_uuid')->references('uuid')->on('processes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_task_assignments');
    }
}

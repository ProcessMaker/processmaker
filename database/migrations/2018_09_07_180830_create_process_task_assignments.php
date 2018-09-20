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
            $table->uuid('process_uuid');
            $table->string('process_task_uuid', 36);
            $table->uuid('assignment_uuid');
            $table->enum('assignment_type',['user','group']);
            $table->timestamps();

            // indexes
            $table->primary('uuid');
            $table->index('process_uuid');

            //Foreign keys
            //If a process is deleted it also delete its assignments
            $table->foreign('process_uuid')
                ->references('uuid')->on('processes')
                ->onDelete('cascade');
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

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
            $table->increments('id');
            $table->unsignedInteger('process_id');
            $table->string('process_task_id', 36);
            $table->morphs('assignment');
            $table->timestamps();

            // indexes
            $table->index('process_id');

            //Foreign keys
            //If a process is deleted it also delete its assignments
            $table->foreign('process_id')
                ->references('id')->on('processes')
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

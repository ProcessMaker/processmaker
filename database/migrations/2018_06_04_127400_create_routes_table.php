<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRoutesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('parent_route_id')->nullable()->unsigned();
            $table->integer('process_id')->unsigned();
            $table->integer('task_id')->unsigned();
            $table->integer('next_task_id')->unsigned();

            $table->integer('case')->default(0);
            $table->string('type', 25)->default('SEQUENTIAL');
            $table->integer('default')->default(0);
            $table->string('condition', 512)->default('');
            $table->boolean('to_last_user')->default(false);
            $table->boolean('optional')->default(false);
            $table->boolean('send_email')->default(true);
            $table->integer('source_anchor')->default(1);
            $table->integer('target_anchor')->default(0);
            $table->integer('to_port')->default(1);
            $table->integer('from_port')->default(2);

            // Setup relationship for Route we belong to
            $table->foreign('parent_route_id')->references('id')->on('routes')->onDelete('cascade');
            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            // Setup relationship for Task we belong to
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            // Setup relationship for Task we belong to
            $table->foreign('next_task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('routes');
    }

}

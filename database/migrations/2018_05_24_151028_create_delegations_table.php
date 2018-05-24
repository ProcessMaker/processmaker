<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDelegationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delegations', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->unsignedInteger('application_id');
            $table->integer('index')->default(0);
            $table->integer('previous')->default(0);
            $table->integer('last_index')->default(0);
            $table->unsignedInteger('task_id');
            $table->string('type', 32)->default('normal');
            $table->integer('thread')->default(0);
            $table->string('thread_status', 32)->default('open');
            $table->string('priority', 32)->default('3');
            $table->dateTime('delegate_date');
            $table->dateTime('init_date')->nullable();
            $table->dateTime('finish_date')->nullable();
            $table->dateTime('task_due_date')->nullable();
            $table->dateTime('risk_date')->nullable();
            $table->float('duration', 10, 0)->default(0);
            $table->float('queue_duration', 10, 0)->default(0);
            $table->float('delay_duration', 10, 0)->default(0);
            $table->boolean('started')->default(0);
            $table->boolean('finished')->default(0);
            $table->boolean('delayed')->default(0);
            $table->text('data');
            $table->float('app_overdue_percentage', 10, 0)->default(0);
            $table->unsignedInteger('user_id')->default(null)->index('userididx');
            $table->index(['application_id', 'index']);

            $table->foreign('application_id')->references('id')->on('APPLICATION');
            $table->foreign('task_id')->references('id')->on('tasks');
            $table->foreign('user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delegations');
    }
}

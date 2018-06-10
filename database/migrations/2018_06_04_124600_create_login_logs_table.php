<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoginLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_logs', function(Blueprint $table)
        {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->unsignedInteger('user_id');
            $table->string('status', 100)->default('');
            $table->string('ip', 15)->default('');
            $table->string('sid', 100)->default('');
            $table->dateTime('init_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('client_hostname', 100);
            $table->index(['sid','user_id','status','end_date'], 'indexLoginLogSelect');

            // setup relationship for User we belong to
            $table->foreign('user_id')->references('id')->on('users')->ondelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('login_logs');
    }
}

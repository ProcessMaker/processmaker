<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function(Blueprint $table)
        {
            $table->increments('id');
            $table->uuid('uid')->unique()->index('indexSession');
            $table->string('status', 16)->default('ACTIVE');
            $table->unsignedInteger('user_id');
            $table->string('remote_ip', 32)->default('0.0.0.0');
            $table->string('init_date', 19)->default('');
            $table->string('due_date', 19)->default('');
            $table->string('end_date', 19)->default('');

            // setup relationship for users we belong to
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
        Schema::dropIfExists('sessions');
    }
}

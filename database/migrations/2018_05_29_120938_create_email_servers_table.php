<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEmailServersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_servers', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid');
            $table->string('engine')->default('');
            $table->string('server')->default('');
            $table->integer('port')->default(0);
            $table->integer('rauth')->default(0);
            $table->string('account', 256)->default('');
            $table->string('password', 256)->default('');
            $table->string('from_mail', 256)->nullable()->default('');
            $table->string('from_name', 256)->nullable()->default('');
            $table->string('smtp_secure', 3)->default('no');
            $table->integer('try_send_inmediatly')->default(0);
            $table->string('mail_to', 256)->nullable()->default('');
            $table->integer('by_default')->default(0);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('email_servers');
    }

}

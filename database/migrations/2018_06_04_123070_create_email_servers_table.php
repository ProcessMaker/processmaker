<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->enum('engine', ['MAIL', 'PHPMAILER'])->default('MAIL');
            $table->string('server')->default('');
            $table->integer('port')->default(0);
            $table->boolean('rauth')->default(false);
            $table->string('account', 256)->default('');
            $table->string('password', 256)->default('');
            $table->string('from_mail', 256)->nullable()->default('');
            $table->string('from_name', 256)->nullable()->default('');
            $table->string('smtp_secure', 3)->default('NO');
            $table->boolean('try_send_inmediatly')->default(false);
            $table->string('mail_to', 256)->nullable()->default('');
            $table->boolean('by_default')->default(false);
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
        Schema::dropIfExists('email_servers');
    }
}

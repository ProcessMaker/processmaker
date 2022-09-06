<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('password');

            $table->string('firstname', 50)->nullable();
            $table->string('lastname', 50)->nullable();
            $table->string('username', 255)->unique();

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal')->nullable();
            $table->string('country')->nullable();

            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('cell')->nullable();

            $table->string('title')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('timezone')->nullable()->default('America/Los_Angeles');
            $table->string('datetime_format')->nullable()->default('m/d/Y H:i');
            $table->string('language')->nullable()->default('en');

            $table->boolean('is_administrator')->default(false);

            $table->date('expires_at')->nullable();
            $table->dateTime('loggedin_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}

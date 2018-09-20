<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->uuid('uuid');
            $table->primary('uuid');
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
            $table->string('timezone')->nullable();
            $table->string('language')->nullable();

            $table->date('expires_at')->nullable();
            $table->date('loggedin_at')->nullable();

            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUSERSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->uuid('uid', 32)->unique();
			$table->string('username', 100);
			$table->string('password', 128);
			$table->string('firstname', 50)->nullable();
			$table->string('lastname', 50)->nullable();
			$table->string('email', 100)->nullable();
			$table->date('expires_at')->nullable();
			$table->timestamps();
			$table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
			$table->string('country')->nullable();
			$table->string('city')->nullable();
			$table->string('location')->nullable();
			$table->string('address')->nullable();
			$table->string('phone')->nullable();
			$table->string('fax')->nullable();
			$table->string('cell')->nullable();
			$table->string('postal')->nullable();
			$table->unsignedInteger('department_id')->nullable();
			$table->string('title')->nullable();
			$table->date('birthdate')->nullable();
			$table->unsignedInteger('role_id')->nullable();
			$table->string('time_zone')->nullable();
			$table->string('lang')->nullable();
			$table->dateTime('last_login')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}

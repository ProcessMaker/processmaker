<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateROLESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('roles', function(Blueprint $table)
		{
			$table->increments('id');
			$table->uuid('uid')->unique();
			$table->string('name');
			$table->string('description');
			$table->string('code');
			$table->timestamps();
			$table->enum('status', ['ACTIVE', 'DISABLED'])->default('ACTIVE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::disableForeignKeyConstraints();
		Schema::drop('roles');
		Schema::enableForeignKeyConstraints();
	}

}

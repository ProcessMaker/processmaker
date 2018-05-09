<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePERMISSIONSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('permissions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->uuid('uid')->unique();
			$table->string('code')->index('indexPermissionsCode');
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
		Schema::drop('permissions');
	}

}

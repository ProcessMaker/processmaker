<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCONFIGURATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CONFIGURATION', function(Blueprint $table)
		{
			$table->string('CFG_UID', 32)->default('');
			$table->string('OBJ_UID', 128)->default('');
			$table->text('CFG_VALUE', 16777215);
			$table->string('PRO_UID', 32)->default('');
			$table->string('USR_UID', 32)->default('');
			$table->string('APP_UID', 32)->default('');
			$table->primary(['CFG_UID','OBJ_UID','PRO_UID','USR_UID','APP_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CONFIGURATION');
	}

}

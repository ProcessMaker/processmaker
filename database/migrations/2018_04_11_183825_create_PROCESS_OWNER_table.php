<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePROCESSOWNERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('PROCESS_OWNER', function(Blueprint $table)
		{
			$table->string('OWN_UID', 32)->default('');
			$table->string('PRO_UID', 32)->default('');
			$table->primary(['OWN_UID','PRO_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('PROCESS_OWNER');
	}

}

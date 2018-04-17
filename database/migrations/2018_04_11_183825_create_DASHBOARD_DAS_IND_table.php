<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDASHBOARDDASINDTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DASHBOARD_DAS_IND', function(Blueprint $table)
		{
			$table->string('DAS_UID', 32)->default('');
			$table->string('OWNER_UID', 32)->default('');
			$table->string('OWNER_TYPE', 15)->default('');
			$table->primary(['DAS_UID','OWNER_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('DASHBOARD_DAS_IND');
	}

}

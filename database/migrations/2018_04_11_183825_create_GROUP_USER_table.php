<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGROUPUSERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('GROUP_USER', function(Blueprint $table)
		{
			$table->string('GRP_UID', 32)->default('0');
			$table->string('USR_UID', 32)->default('0')->index('indexForUsrUid');
			$table->primary(['GRP_UID','USR_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('GROUP_USER');
	}

}

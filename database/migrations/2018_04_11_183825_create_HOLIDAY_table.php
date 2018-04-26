<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHOLIDAYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('HOLIDAY', function(Blueprint $table)
		{
			$table->integer('HLD_UID', true);
			$table->string('HLD_DATE', 10)->default('0000-00-00');
			$table->string('HLD_DESCRIPTION', 200)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('HOLIDAY');
	}

}

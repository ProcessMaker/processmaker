<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateISOSUBDIVISIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ISO_SUBDIVISION', function(Blueprint $table)
		{
			$table->string('IC_UID', 2)->default('');
			$table->string('IS_UID', 4)->default('');
			$table->string('IS_NAME')->default('');
			$table->primary(['IC_UID','IS_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ISO_SUBDIVISION');
	}

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateISOLOCATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ISO_LOCATION', function(Blueprint $table)
		{
			$table->string('IC_UID', 2)->default('');
			$table->string('IL_UID', 5)->default('');
			$table->string('IL_NAME')->nullable();
			$table->string('IL_NORMAL_NAME')->nullable();
			$table->string('IS_UID', 4)->nullable();
			$table->primary(['IC_UID','IL_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ISO_LOCATION');
	}

}

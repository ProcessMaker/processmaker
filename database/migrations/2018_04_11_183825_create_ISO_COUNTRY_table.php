<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateISOCOUNTRYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ISO_COUNTRY', function(Blueprint $table)
		{
			$table->string('IC_UID', 2)->default('')->primary();
			$table->string('IC_NAME')->nullable();
			$table->string('IC_SORT_ORDER')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ISO_COUNTRY');
	}

}

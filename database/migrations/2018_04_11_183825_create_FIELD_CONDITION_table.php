<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFIELDCONDITIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('FIELD_CONDITION', function(Blueprint $table)
		{
			$table->string('FCD_UID', 32)->default('')->primary();
			$table->string('FCD_FUNCTION', 50);
			$table->text('FCD_FIELDS', 16777215)->nullable();
			$table->text('FCD_CONDITION', 16777215)->nullable();
			$table->text('FCD_EVENTS', 16777215)->nullable();
			$table->text('FCD_EVENT_OWNERS', 16777215)->nullable();
			$table->string('FCD_STATUS', 10)->nullable();
			$table->string('FCD_DYN_UID', 32);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('FIELD_CONDITION');
	}

}

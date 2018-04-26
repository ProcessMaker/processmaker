<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSWIMLANESELEMENTSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('SWIMLANES_ELEMENTS', function(Blueprint $table)
		{
			$table->string('SWI_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('');
			$table->string('SWI_TYPE', 20)->default('LINE');
			$table->integer('SWI_X')->default(0);
			$table->integer('SWI_Y')->default(0);
			$table->integer('SWI_WIDTH')->default(0);
			$table->integer('SWI_HEIGHT')->default(0);
			$table->string('SWI_NEXT_UID', 32)->nullable()->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('SWIMLANES_ELEMENTS');
	}

}

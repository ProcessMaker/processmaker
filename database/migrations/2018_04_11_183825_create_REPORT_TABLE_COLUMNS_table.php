<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateREPORTTABLECOLUMNSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('report_table_columns', function(Blueprint $table)
		{
			$table->increments('id');
			$table->uuid('uid')->unique();
			$table->unsignedInteger('report_table_id');
			$table->string('name');
			$table->string('dynaform_name')->nullable();
			$table->unsignedInteger('dynaform_id')->nullable();
			$table->boolean('filter')->default(false);
			$table->unsignedInteger('process_variable_id')->nullable();
			$table->foreign('report_table_id')->references('id')->on('additional_tables')->onDelete('CASCADE');
			// @todo Add foreign key constraints for dynaform id when dynaform table schema is updated
			// @todo Add foreign key constraints for process variable id when table schema is updated
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('report_table_columns');
	}

}

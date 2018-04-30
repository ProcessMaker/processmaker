<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateADDITIONALTABLESTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('additional_tables', function(Blueprint $table)
		{
			$table->increments('id');
			$table->uuid('uid')->unique();
			$table->string('name');
			$table->text('description')->nullable();
			$table->enum('type', ['PMTABLE', 'NORMAL', 'GRID'])->default('PMTABLE');
			$table->string('grid')->nullable();
			$table->string('tags')->nullable();

			$table->uuid('db_source_id')->nullable();
			$table->uuid('process_id')->nullable()->index('indexAdditionalProcess');

			/**
			 * @todo Move this additional tables to AFTER db sources and process tables are added
			 */
			//$table->foreign('db_source_id')->references('id')->on('db_sources');
			//$table->foreign('process_id')->references('id')->on('processes');

		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ADDITIONAL_TABLES');
	}

}

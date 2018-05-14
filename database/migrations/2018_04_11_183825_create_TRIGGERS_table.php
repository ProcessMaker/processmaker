<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTRIGGERSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('triggers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
			$table->uuid('uid');
			$table->text('title');
			$table->text('description')->nullable();
			$table->unsignedInteger('process_id');
			$table->enum('type', ['SCRIPT'])->default('SCRIPT');

			// @todo Find out what webbot and param are meant for
			$table->text('webbot');
			$table->text('param')->nullable();

			// Setup relationship for process we belong to
			$table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('triggers');
	}

}

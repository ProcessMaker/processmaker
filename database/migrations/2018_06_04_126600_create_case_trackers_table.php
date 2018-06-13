<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCaseTrackersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('case_trackers', function(Blueprint $table)
		{
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('process_id')->unsigned();
			$table->string('map_type', 10)->default('0');
			$table->integer('derivation_history')->default(0);
			$table->integer('message_history')->default(0);

            // Setup relationship for Process we belong to
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
		Schema::drop('case_trackers');
	}

}

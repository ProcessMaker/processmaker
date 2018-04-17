<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEVENTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('EVENT', function(Blueprint $table)
		{
			$table->string('EVN_UID', 32)->default('')->index('indexEventTable');
			$table->string('PRO_UID', 32)->default('');
			$table->string('EVN_STATUS', 16)->default('OPEN');
			$table->string('EVN_WHEN_OCCURS', 32)->nullable()->default('SINGLE');
			$table->string('EVN_RELATED_TO', 16)->nullable()->default('SINGLE');
			$table->string('TAS_UID', 32)->default('');
			$table->string('EVN_TAS_UID_FROM', 32)->nullable()->default('');
			$table->string('EVN_TAS_UID_TO', 32)->nullable()->default('');
			$table->float('EVN_TAS_ESTIMATED_DURATION', 10, 0)->nullable()->default(0);
			$table->string('EVN_TIME_UNIT', 10)->default('DAYS');
			$table->float('EVN_WHEN', 10, 0)->default(0);
			$table->boolean('EVN_MAX_ATTEMPTS')->default(3);
			$table->string('EVN_ACTION', 50)->default('');
			$table->text('EVN_CONDITIONS', 16777215)->nullable();
			$table->text('EVN_ACTION_PARAMETERS', 16777215)->nullable();
			$table->string('TRI_UID', 32)->nullable()->default('');
			$table->integer('EVN_POSX')->default(0);
			$table->integer('EVN_POSY')->default(0);
			$table->string('EVN_TYPE', 32)->nullable()->default('');
			$table->string('TAS_EVN_UID', 32)->nullable()->default('');
			$table->index(['EVN_STATUS','EVN_ACTION','PRO_UID'], 'indexStatusActionProcess');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('EVENT');
	}

}

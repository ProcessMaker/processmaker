<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDASHBOARDINDICATORTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DASHBOARD_INDICATOR', function(Blueprint $table)
		{
			$table->string('DAS_IND_UID', 32)->default('')->primary();
			$table->string('DAS_UID', 32)->default('');
			$table->string('DAS_IND_TYPE', 32)->default('');
			$table->string('DAS_IND_TITLE')->default('');
			$table->decimal('DAS_IND_GOAL', 7)->nullable()->default(0.00);
			$table->boolean('DAS_IND_DIRECTION')->default(2);
			$table->string('DAS_UID_PROCESS', 32)->default('');
			$table->string('DAS_IND_FIRST_FIGURE', 32)->nullable()->default('');
			$table->string('DAS_IND_FIRST_FREQUENCY', 32)->nullable()->default('');
			$table->string('DAS_IND_SECOND_FIGURE', 32)->nullable()->default('');
			$table->string('DAS_IND_SECOND_FREQUENCY', 32)->nullable()->default('');
			$table->dateTime('DAS_IND_CREATE_DATE');
			$table->dateTime('DAS_IND_UPDATE_DATE')->nullable();
			$table->boolean('DAS_IND_STATUS')->default(1);
			$table->index(['DAS_UID','DAS_IND_TYPE'], 'indexDashboard');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('DASHBOARD_INDICATOR');
	}

}

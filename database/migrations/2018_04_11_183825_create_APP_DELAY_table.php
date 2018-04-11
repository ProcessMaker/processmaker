<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPDELAYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_DELAY', function(Blueprint $table)
		{
			$table->string('APP_DELAY_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('0');
			$table->string('APP_UID', 32)->default('0')->index('indexAppUid');
			$table->integer('APP_NUMBER')->nullable()->default(0)->index('INDEX_APP_NUMBER');
			$table->integer('APP_THREAD_INDEX')->default(0);
			$table->integer('APP_DEL_INDEX')->default(0);
			$table->string('APP_TYPE', 20)->default('0');
			$table->string('APP_STATUS', 20)->default('0');
			$table->string('APP_NEXT_TASK', 32)->nullable()->default('0');
			$table->string('APP_DELEGATION_USER', 32)->nullable()->default('0');
			$table->string('APP_ENABLE_ACTION_USER', 32)->default('0');
			$table->dateTime('APP_ENABLE_ACTION_DATE');
			$table->string('APP_DISABLE_ACTION_USER', 32)->nullable()->default('0');
			$table->dateTime('APP_DISABLE_ACTION_DATE')->nullable();
			$table->dateTime('APP_AUTOMATIC_DISABLED_DATE')->nullable();
			$table->integer('APP_DELEGATION_USER_ID')->nullable()->default(0)->index('INDEX_USR_ID');
			$table->integer('PRO_ID')->nullable()->default(0)->index('INDEX_PRO_ID');
			$table->index(['PRO_UID','APP_UID','APP_THREAD_INDEX','APP_DEL_INDEX','APP_NEXT_TASK','APP_DELEGATION_USER','APP_DISABLE_ACTION_USER'], 'indexAppDelay');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_DELAY');
	}

}

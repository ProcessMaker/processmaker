<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWEBENTRYEVENTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('WEB_ENTRY_EVENT', function(Blueprint $table)
		{
			$table->string('WEE_UID', 32)->primary();
			$table->text('WEE_TITLE', 16777215)->nullable();
			$table->text('WEE_DESCRIPTION', 16777215)->nullable();
			$table->string('PRJ_UID', 32);
			$table->string('EVN_UID', 32);
			$table->string('ACT_UID', 32);
			$table->string('DYN_UID', 32)->nullable();
			$table->string('USR_UID', 32)->nullable();
			$table->string('WEE_STATUS', 10)->default('ENABLED');
			$table->string('WEE_WE_UID', 32)->default('');
			$table->string('WEE_WE_TAS_UID', 32)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('WEB_ENTRY_EVENT');
	}

}

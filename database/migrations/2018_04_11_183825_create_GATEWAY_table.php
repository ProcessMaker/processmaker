<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGATEWAYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('GATEWAY', function(Blueprint $table)
		{
			$table->string('GAT_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('');
			$table->string('TAS_UID', 32)->default('');
			$table->string('GAT_NEXT_TASK', 32)->default('');
			$table->integer('GAT_X')->default(0);
			$table->integer('GAT_Y')->default(0);
			$table->string('GAT_TYPE', 32)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('GATEWAY');
	}

}

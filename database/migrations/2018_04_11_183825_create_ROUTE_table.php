<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateROUTETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ROUTE', function(Blueprint $table)
		{
			$table->string('ROU_UID', 32)->default('')->primary();
			$table->string('ROU_PARENT', 32)->default('0');
			$table->string('PRO_UID', 32)->default('');
			$table->string('TAS_UID', 32)->default('');
			$table->string('ROU_NEXT_TASK', 32)->default('0');
			$table->integer('ROU_CASE')->default(0);
			$table->string('ROU_TYPE', 25)->default('SEQUENTIAL');
			$table->integer('ROU_DEFAULT')->default(0);
			$table->string('ROU_CONDITION', 512)->default('');
			$table->string('ROU_TO_LAST_USER', 20)->default('FALSE');
			$table->string('ROU_OPTIONAL', 20)->default('FALSE');
			$table->string('ROU_SEND_EMAIL', 20)->default('TRUE');
			$table->integer('ROU_SOURCEANCHOR')->nullable()->default(1);
			$table->integer('ROU_TARGETANCHOR')->nullable()->default(0);
			$table->integer('ROU_TO_PORT')->default(1);
			$table->integer('ROU_FROM_PORT')->default(2);
			$table->string('ROU_EVN_UID', 32)->default('');
			$table->string('GAT_UID', 32)->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ROUTE');
	}

}

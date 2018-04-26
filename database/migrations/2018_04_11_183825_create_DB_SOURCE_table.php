<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDBSOURCETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DB_SOURCE', function(Blueprint $table)
		{
			$table->string('DBS_UID', 32)->default('');
			$table->string('PRO_UID', 32)->default('0')->index('indexDBSource');
			$table->string('DBS_TYPE', 8)->default('0');
			$table->string('DBS_SERVER', 100)->default('0');
			$table->string('DBS_DATABASE_NAME', 100)->default('0');
			$table->string('DBS_USERNAME', 32)->default('0');
			$table->string('DBS_PASSWORD', 256)->nullable()->default('');
			$table->integer('DBS_PORT')->nullable()->default(0);
			$table->string('DBS_ENCODE', 32)->nullable()->default('');
			$table->string('DBS_CONNECTION_TYPE', 32)->nullable()->default('NORMAL');
			$table->string('DBS_TNS', 256)->nullable()->default('');
			$table->text('DBS_DESCRIPTION', 16777215)->nullable();
			$table->primary(['DBS_UID','PRO_UID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('DB_SOURCE');
	}

}

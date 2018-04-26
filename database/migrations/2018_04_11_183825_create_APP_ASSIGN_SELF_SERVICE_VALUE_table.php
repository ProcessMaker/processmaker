<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPASSIGNSELFSERVICEVALUETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_ASSIGN_SELF_SERVICE_VALUE', function(Blueprint $table)
		{
			$table->integer('ID', true);
			$table->string('APP_UID', 32);
			$table->integer('DEL_INDEX')->default(0);
			$table->string('PRO_UID', 32);
			$table->string('TAS_UID', 32);
			$table->text('GRP_UID', 16777215);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_ASSIGN_SELF_SERVICE_VALUE');
	}

}

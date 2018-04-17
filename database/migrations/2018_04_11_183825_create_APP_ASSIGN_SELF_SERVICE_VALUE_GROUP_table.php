<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPASSIGNSELFSERVICEVALUEGROUPTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_ASSIGN_SELF_SERVICE_VALUE_GROUP', function(Blueprint $table)
		{
			$table->integer('ID')->default(0)->index('indexId');
			$table->string('GRP_UID', 32);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_ASSIGN_SELF_SERVICE_VALUE_GROUP');
	}

}

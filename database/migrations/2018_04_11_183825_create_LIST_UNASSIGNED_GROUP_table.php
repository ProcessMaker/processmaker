<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLISTUNASSIGNEDGROUPTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('LIST_UNASSIGNED_GROUP', function(Blueprint $table)
		{
			$table->string('UNA_UID', 32)->default('');
			$table->string('USR_UID', 32)->default('');
			$table->string('TYPE')->default('');
			$table->string('TYP_UID', 32)->default('');
			$table->integer('USR_ID')->nullable()->default(0)->index('INDEX_USR_ID');
			$table->primary(['UNA_UID','USR_UID','TYPE']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('LIST_UNASSIGNED_GROUP');
	}

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFIELDSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('FIELDS', function(Blueprint $table)
		{
			$table->string('FLD_UID', 32)->default('');
			$table->integer('FLD_ID', true);
			$table->string('ADD_TAB_UID', 32)->default('');
			$table->integer('ADD_TAB_ID')->nullable();
			$table->string('FLD_NAME', 60)->default('');
			$table->string('FLD_DYN_NAME', 128)->nullable()->default('');
			$table->string('FLD_DYN_UID', 128)->nullable()->default('');
			$table->boolean('FLD_FILTER')->nullable()->default(0);
			$table->integer('VAR_ID');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('FIELDS');
	}

}

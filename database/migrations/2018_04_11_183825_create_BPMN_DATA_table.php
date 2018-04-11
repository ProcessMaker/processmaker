<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNDATATable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_DATA', function(Blueprint $table)
		{
			$table->string('DAT_UID', 32)->default('')->index('BPMN_DATA_I_1');
			$table->string('PRJ_UID', 32)->index('BPMN_DATA_I_2');
			$table->string('PRO_UID', 32)->nullable()->default('')->index('BPMN_DATA_I_3');
			$table->string('DAT_NAME')->nullable();
			$table->string('DAT_TYPE', 20);
			$table->boolean('DAT_IS_COLLECTION')->nullable()->default(0);
			$table->string('DAT_ITEM_KIND', 20)->default('INFORMATION');
			$table->integer('DAT_CAPACITY')->nullable()->default(0);
			$table->boolean('DAT_IS_UNLIMITED')->nullable()->default(0);
			$table->string('DAT_STATE')->nullable()->default('');
			$table->boolean('DAT_IS_GLOBAL')->nullable()->default(0);
			$table->string('DAT_OBJECT_REF', 32)->nullable()->default('');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_DATA');
	}

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBPMNLANESETTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BPMN_LANESET', function(Blueprint $table)
		{
			$table->string('LNS_UID', 32)->default('')->index('BPMN_LANESET_I_1');
			$table->string('PRJ_UID', 32)->index('BPMN_LANESET_I_2');
			$table->string('PRO_UID', 32)->nullable()->index('BPMN_LANESET_I_3');
			$table->string('LNS_NAME')->nullable();
			$table->string('LNS_PARENT_LANE', 32)->nullable();
			$table->boolean('LNS_IS_HORIZONTAL')->nullable()->default(1);
			$table->text('LNS_STATE', 16777215)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('BPMN_LANESET');
	}

}

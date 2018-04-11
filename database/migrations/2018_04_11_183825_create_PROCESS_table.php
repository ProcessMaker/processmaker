<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePROCESSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('PROCESS', function(Blueprint $table)
		{
			$table->string('PRO_UID', 32)->default('')->unique();
			$table->integer('PRO_ID', true);
			$table->text('PRO_TITLE', 16777215);
			$table->text('PRO_DESCRIPTION', 16777215)->nullable();
			$table->string('PRO_PARENT', 32)->default('0');
			$table->float('PRO_TIME', 10, 0)->default(1);
			$table->string('PRO_TIMEUNIT', 20)->default('DAYS');
			$table->string('PRO_STATUS', 20)->default('ACTIVE');
			$table->char('PRO_TYPE_DAY', 1)->default(0);
			$table->string('PRO_TYPE', 256)->default('NORMAL');
			$table->string('PRO_ASSIGNMENT', 20)->default('FALSE');
			$table->boolean('PRO_SHOW_MAP')->default(1);
			$table->boolean('PRO_SHOW_MESSAGE')->default(1);
			$table->boolean('PRO_SUBPROCESS')->default(0);
			$table->string('PRO_TRI_CREATE', 32)->default('');
			$table->string('PRO_TRI_OPEN', 32)->default('');
			$table->string('PRO_TRI_DELETED', 32)->default('');
			$table->string('PRO_TRI_CANCELED', 32)->default('');
			$table->string('PRO_TRI_PAUSED', 32)->default('');
			$table->string('PRO_TRI_REASSIGNED', 32)->default('');
			$table->string('PRO_TRI_UNPAUSED', 32)->default('');
			$table->string('PRO_TYPE_PROCESS', 32)->default('PUBLIC');
			$table->boolean('PRO_SHOW_DELEGATE')->default(1);
			$table->boolean('PRO_SHOW_DYNAFORM')->default(0);
			$table->string('PRO_CATEGORY', 48)->default('');
			$table->string('PRO_SUB_CATEGORY', 48)->default('');
			$table->integer('PRO_INDUSTRY')->default(1);
			$table->dateTime('PRO_UPDATE_DATE')->nullable();
			$table->dateTime('PRO_CREATE_DATE');
			$table->string('PRO_CREATE_USER', 32)->default('');
			$table->integer('PRO_HEIGHT')->default(5000);
			$table->integer('PRO_WIDTH')->default(10000);
			$table->integer('PRO_TITLE_X')->default(0);
			$table->integer('PRO_TITLE_Y')->default(6);
			$table->integer('PRO_DEBUG')->default(0);
			$table->text('PRO_DYNAFORMS', 16777215)->nullable();
			$table->string('PRO_DERIVATION_SCREEN_TPL', 128)->nullable()->default('');
			$table->decimal('PRO_COST', 7)->nullable()->default(0.00);
			$table->string('PRO_UNIT_COST', 50)->nullable()->default('');
			$table->integer('PRO_ITEE')->default(0);
			$table->text('PRO_ACTION_DONE', 16777215)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('PROCESS');
	}

}

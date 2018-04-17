<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLISTPARTICIPATEDLASTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('LIST_PARTICIPATED_LAST', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('');
			$table->string('USR_UID', 32)->default('')->index('usrIndex');
			$table->integer('DEL_INDEX')->default(0);
			$table->string('TAS_UID', 32)->default('');
			$table->string('PRO_UID', 32)->default('');
			$table->integer('APP_NUMBER')->default(0);
			$table->text('APP_TITLE', 16777215)->nullable();
			$table->text('APP_PRO_TITLE', 16777215)->nullable();
			$table->text('APP_TAS_TITLE', 16777215)->nullable();
			$table->string('APP_STATUS', 20)->nullable()->default('0');
			$table->string('DEL_PREVIOUS_USR_UID', 32)->nullable()->default('');
			$table->string('DEL_PREVIOUS_USR_USERNAME', 100)->nullable()->default('');
			$table->string('DEL_PREVIOUS_USR_FIRSTNAME', 50)->nullable()->default('');
			$table->string('DEL_PREVIOUS_USR_LASTNAME', 50)->nullable()->default('');
			$table->string('DEL_CURRENT_USR_USERNAME', 100)->nullable()->default('');
			$table->string('DEL_CURRENT_USR_FIRSTNAME', 50)->nullable()->default('');
			$table->string('DEL_CURRENT_USR_LASTNAME', 50)->nullable()->default('');
			$table->string('DEL_CURRENT_TAS_TITLE')->default('');
			$table->dateTime('DEL_DELEGATE_DATE')->index('delDelegateDate');
			$table->dateTime('DEL_INIT_DATE')->nullable();
			$table->dateTime('DEL_DUE_DATE')->nullable();
			$table->string('DEL_PRIORITY', 32)->default('3');
			$table->string('DEL_THREAD_STATUS', 32)->default('OPEN');
			$table->integer('PRO_ID')->nullable()->default(0)->index('INDEX_PRO_ID');
			$table->integer('USR_ID')->nullable()->default(0)->index('INDEX_USR_ID');
			$table->integer('TAS_ID')->nullable()->default(0)->index('INDEX_TAS_ID');
			$table->boolean('APP_STATUS_ID')->nullable()->default(0);
			$table->primary(['APP_UID','USR_UID','DEL_INDEX']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('LIST_PARTICIPATED_LAST');
	}

}

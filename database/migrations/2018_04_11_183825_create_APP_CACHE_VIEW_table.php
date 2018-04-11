<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPCACHEVIEWTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_CACHE_VIEW', function(Blueprint $table)
		{
			$table->string('APP_UID', 32)->default('')->index('indexAppUid');
			$table->integer('DEL_INDEX')->default(0);
			$table->integer('DEL_LAST_INDEX')->default(0);
			$table->integer('APP_NUMBER')->default(0)->index('indexAppNumber');
			$table->string('APP_STATUS', 32)->default('');
			$table->string('USR_UID', 32)->default('')->index('indexUsrUid');
			$table->string('PREVIOUS_USR_UID', 32)->nullable()->default('')->index('indexPrevUsrUid');
			$table->string('TAS_UID', 32)->default('')->index('taskUid');
			$table->string('PRO_UID', 32)->default('')->index('indexProUid');
			$table->dateTime('DEL_DELEGATE_DATE');
			$table->dateTime('DEL_INIT_DATE')->nullable();
			$table->dateTime('DEL_FINISH_DATE')->nullable();
			$table->dateTime('DEL_TASK_DUE_DATE')->nullable();
			$table->dateTime('DEL_RISK_DATE')->nullable();
			$table->string('DEL_THREAD_STATUS', 32)->nullable()->default('OPEN');
			$table->string('APP_THREAD_STATUS', 32)->nullable()->default('OPEN');
			$table->string('APP_TITLE')->default('');
			$table->string('APP_PRO_TITLE')->default('')->index('protitle');
			$table->string('APP_TAS_TITLE')->default('')->index('tastitle');
			$table->string('APP_CURRENT_USER', 128)->nullable()->default('');
			$table->string('APP_DEL_PREVIOUS_USER', 128)->nullable()->default('');
			$table->string('DEL_PRIORITY', 32)->default('3');
			$table->float('DEL_DURATION', 10, 0)->nullable()->default(0);
			$table->float('DEL_QUEUE_DURATION', 10, 0)->nullable()->default(0);
			$table->float('DEL_DELAY_DURATION', 10, 0)->nullable()->default(0);
			$table->boolean('DEL_STARTED')->default(0);
			$table->boolean('DEL_FINISHED')->default(0);
			$table->boolean('DEL_DELAYED')->default(0);
			$table->dateTime('APP_CREATE_DATE');
			$table->dateTime('APP_FINISH_DATE')->nullable();
			$table->dateTime('APP_UPDATE_DATE')->index('appupdatedate');
			$table->float('APP_OVERDUE_PERCENTAGE', 10, 0);
			$table->primary(['APP_UID','DEL_INDEX']);
			$table->index(['USR_UID','DEL_THREAD_STATUS','APP_STATUS'], 'indexUsrUidThreadStatusAppStatus');
			$table->index(['USR_UID','APP_STATUS'], 'indexAppUser');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_CACHE_VIEW');
	}

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAPPDELEGATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_DELEGATION', function(Blueprint $table)
		{
			$table->increments('id');
			$table->unsignedInteger('application_id');
			$table->integer('DEL_INDEX')->default(0);
			$table->integer('DEL_PREVIOUS')->default(0);
			$table->integer('DEL_LAST_INDEX')->default(0);
			$table->unsignedInteger('task_id');
			$table->string('DEL_TYPE', 32)->default('NORMAL');
			$table->integer('DEL_THREAD')->default(0);
			$table->string('DEL_THREAD_STATUS', 32)->default('OPEN');
			$table->string('DEL_PRIORITY', 32)->default('3');
			$table->dateTime('DEL_DELEGATE_DATE');
			$table->dateTime('DEL_INIT_DATE')->nullable();
			$table->dateTime('DEL_FINISH_DATE')->nullable();
			$table->dateTime('DEL_TASK_DUE_DATE')->nullable();
			$table->dateTime('DEL_RISK_DATE')->nullable();
			$table->float('DEL_DURATION', 10, 0)->nullable()->default(0);
			$table->float('DEL_QUEUE_DURATION', 10, 0)->nullable()->default(0);
			$table->float('DEL_DELAY_DURATION', 10, 0)->nullable()->default(0);
			$table->boolean('DEL_STARTED')->nullable()->default(0);
			$table->boolean('DEL_FINISHED')->nullable()->default(0);
			$table->boolean('DEL_DELAYED')->nullable()->default(0);
			$table->text('DEL_DATA', 16777215);
			$table->float('APP_OVERDUE_PERCENTAGE', 10, 0)->default(0);
			$table->unsignedInteger('user_id')->nullable()->default(null)->index('userIdIdx');
			$table->index(['application_id','DEL_INDEX']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_DELEGATION');
	}

}

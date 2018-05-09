<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTASKTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('TASK', function(Blueprint $table)
		{
			$table->increments('id');
			$table->unsignedInteger('process_id');
			$table->uuid('uid')->index('indexTasUid');
			$table->text('TAS_TITLE', 16777215);
			$table->text('TAS_DESCRIPTION', 16777215)->nullable();
			$table->text('TAS_DEF_TITLE', 16777215)->nullable();
			$table->text('TAS_DEF_SUBJECT_MESSAGE', 16777215)->nullable();
			$table->text('TAS_DEF_PROC_CODE', 16777215)->nullable();
			$table->text('TAS_DEF_MESSAGE', 16777215)->nullable();
			$table->text('TAS_DEF_DESCRIPTION', 16777215)->nullable();
			$table->string('TAS_TYPE', 50)->default('NORMAL');
			$table->float('TAS_DURATION', 10, 0)->default(0);
			$table->string('TAS_DELAY_TYPE', 30)->default('');
			$table->float('TAS_TEMPORIZER', 10, 0)->default(0);
			$table->char('TAS_TYPE_DAY', 1)->default(1);
			$table->string('TAS_TIMEUNIT', 20)->default('DAYS');
			$table->string('TAS_ALERT', 20)->default('FALSE');
			$table->string('TAS_PRIORITY_VARIABLE', 100)->default('');
			$table->string('TAS_ASSIGN_TYPE', 30)->default('BALANCED');
			$table->string('TAS_ASSIGN_VARIABLE', 100)->default('@@SYS_NEXT_USER_TO_BE_ASSIGNED');
			$table->string('TAS_GROUP_VARIABLE', 100)->nullable();
			$table->string('TAS_MI_INSTANCE_VARIABLE', 100)->default('@@SYS_VAR_TOTAL_INSTANCE');
			$table->string('TAS_MI_COMPLETE_VARIABLE', 100)->default('@@SYS_VAR_TOTAL_INSTANCES_COMPLETE');
			$table->string('TAS_ASSIGN_LOCATION', 20)->default('FALSE');
			$table->string('TAS_ASSIGN_LOCATION_ADHOC', 20)->default('FALSE');
			$table->string('TAS_TRANSFER_FLY', 20)->default('FALSE');
			$table->string('TAS_LAST_ASSIGNED', 32)->default('0');
			$table->string('TAS_USER', 32)->default('0');
			$table->string('TAS_CAN_UPLOAD', 20)->default('FALSE');
			$table->string('TAS_VIEW_UPLOAD', 20)->default('FALSE');
			$table->string('TAS_VIEW_ADDITIONAL_DOCUMENTATION', 20)->default('FALSE');
			$table->string('TAS_CAN_CANCEL', 20)->default('FALSE');
			$table->string('TAS_OWNER_APP', 32)->default('');
			$table->string('STG_UID', 32)->default('');
			$table->string('TAS_CAN_PAUSE', 20)->default('FALSE');
			$table->string('TAS_CAN_SEND_MESSAGE', 20)->default('TRUE');
			$table->string('TAS_CAN_DELETE_DOCS', 20)->default('FALSE');
			$table->string('TAS_SELF_SERVICE', 20)->default('FALSE');
			$table->string('TAS_START', 20)->default('FALSE');
			$table->string('TAS_TO_LAST_USER', 20)->default('FALSE');
			$table->string('TAS_SEND_LAST_EMAIL', 20)->default('TRUE');
			$table->string('TAS_DERIVATION', 100)->default('NORMAL');
			$table->integer('TAS_POSX')->default(0);
			$table->integer('TAS_POSY')->default(0);
			$table->integer('TAS_WIDTH')->default(110);
			$table->integer('TAS_HEIGHT')->default(60);
			$table->string('TAS_COLOR', 32)->default('');
			$table->string('TAS_EVN_UID', 32)->default('');
			$table->string('TAS_BOUNDARY', 32)->default('');
			$table->string('TAS_DERIVATION_SCREEN_TPL', 128)->nullable()->default('');
			$table->integer('TAS_SELFSERVICE_TIMEOUT')->nullable()->default(0);
			$table->integer('TAS_SELFSERVICE_TIME')->nullable()->default(0);
			$table->string('TAS_SELFSERVICE_TIME_UNIT', 15)->nullable()->default('');
			$table->string('TAS_SELFSERVICE_TRIGGER_UID', 32)->nullable()->default('');
			$table->string('TAS_SELFSERVICE_EXECUTION', 15)->nullable()->default('EVERY_TIME');
			$table->integer('TAS_NOT_EMAIL_FROM_FORMAT')->nullable()->default(0);
			$table->string('TAS_OFFLINE', 20)->default('FALSE');
			$table->string('TAS_EMAIL_SERVER_UID', 32)->nullable()->default('');
			$table->string('TAS_AUTO_ROOT', 20)->default('FALSE');
			$table->string('TAS_RECEIVE_SERVER_UID', 32)->nullable()->default('');
			$table->string('TAS_RECEIVE_LAST_EMAIL', 20)->default('FALSE');
			$table->integer('TAS_RECEIVE_EMAIL_FROM_FORMAT')->nullable()->default(0);
			$table->string('TAS_RECEIVE_MESSAGE_TYPE', 20)->default('text');
			$table->string('TAS_RECEIVE_MESSAGE_TEMPLATE', 100)->default('alert_message.html');
			$table->text('TAS_RECEIVE_SUBJECT_MESSAGE', 16777215)->nullable();
			$table->text('TAS_RECEIVE_MESSAGE', 16777215)->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('TASK');
	}

}

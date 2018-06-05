<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppMessagetable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('APP_MESSAGE', function(Blueprint $table)
		{
			$table->string('APP_MSG_UID', 32)->primary();
			$table->string('MSG_UID', 32)->nullable();
			$table->string('APP_UID', 32)->default('')->index('indexForAppUid');
			$table->integer('DEL_INDEX')->default(0);
			$table->string('APP_MSG_TYPE', 100)->default('');
			$table->string('APP_MSG_SUBJECT', 150)->default('');
			$table->string('APP_MSG_FROM', 100)->default('');
			$table->text('APP_MSG_TO', 16777215);
			$table->text('APP_MSG_BODY', 16777215);
			$table->dateTime('APP_MSG_DATE');
			$table->text('APP_MSG_CC', 16777215)->nullable();
			$table->text('APP_MSG_BCC', 16777215)->nullable();
			$table->text('APP_MSG_TEMPLATE', 16777215)->nullable();
			$table->string('APP_MSG_STATUS', 20)->nullable()->index('indexForMsgStatus');
			$table->text('APP_MSG_ATTACH', 16777215)->nullable();
			$table->dateTime('APP_MSG_SEND_DATE');
			$table->boolean('APP_MSG_SHOW_MESSAGE')->default(1);
			$table->text('APP_MSG_ERROR', 16777215)->nullable();
			$table->integer('TAS_ID')->nullable()->default(0)->index('INDEX_TAS_ID');
			$table->integer('APP_NUMBER')->nullable()->default(0)->index('INDEX_APP_NUMBER');

            $table->unsignedInteger('application_id');
            // Setup relationship for Application we belong to
            $table->foreign('application_id')->references('id')->on('APPLICATION')->onDelete('cascade');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('APP_MESSAGE');
	}

}

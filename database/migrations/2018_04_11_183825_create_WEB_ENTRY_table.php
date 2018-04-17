<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWEBENTRYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('WEB_ENTRY', function(Blueprint $table)
		{
			$table->string('WE_UID', 32)->primary();
			$table->string('PRO_UID', 32);
			$table->string('TAS_UID', 32);
			$table->string('DYN_UID', 32)->nullable();
			$table->string('USR_UID', 32)->nullable();
			$table->string('WE_METHOD', 4)->nullable()->default('HTML');
			$table->integer('WE_INPUT_DOCUMENT_ACCESS')->nullable()->default(0);
			$table->text('WE_DATA', 16777215)->nullable();
			$table->string('WE_CREATE_USR_UID', 32)->default('');
			$table->string('WE_UPDATE_USR_UID', 32)->nullable()->default('');
			$table->dateTime('WE_CREATE_DATE');
			$table->dateTime('WE_UPDATE_DATE')->nullable();
			$table->string('WE_TYPE', 8)->default('SINGLE');
			$table->text('WE_CUSTOM_TITLE', 16777215)->nullable();
			$table->string('WE_AUTHENTICATION', 14)->default('ANONYMOUS');
			$table->char('WE_HIDE_INFORMATION_BAR', 1)->nullable()->default(1);
			$table->string('WE_CALLBACK', 13)->default('PROCESSMAKER');
			$table->text('WE_CALLBACK_URL', 16777215)->nullable();
			$table->string('WE_LINK_GENERATION', 8)->default('DEFAULT');
			$table->string('WE_LINK_SKIN')->nullable();
			$table->string('WE_LINK_LANGUAGE')->nullable();
			$table->text('WE_LINK_DOMAIN', 16777215)->nullable();
			$table->char('WE_SHOW_IN_NEW_CASE', 1)->nullable()->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('WEB_ENTRY');
	}

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUSERSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('USERS', function(Blueprint $table)
		{
			$table->string('USR_UID', 32)->default('')->index('indexUsrUid');
			$table->integer('USR_ID', true);
			$table->string('USR_USERNAME', 100)->default('');
			$table->string('USR_PASSWORD', 128)->default('');
			$table->string('USR_FIRSTNAME', 50)->default('');
			$table->string('USR_LASTNAME', 50)->default('');
			$table->string('USR_EMAIL', 100)->default('');
			$table->date('USR_DUE_DATE');
			$table->dateTime('USR_CREATE_DATE');
			$table->dateTime('USR_UPDATE_DATE');
			$table->string('USR_STATUS', 32)->default('ACTIVE');
			$table->string('USR_COUNTRY', 3)->default('');
			$table->string('USR_CITY', 3)->default('');
			$table->string('USR_LOCATION', 3)->default('');
			$table->string('USR_ADDRESS')->default('');
			$table->string('USR_PHONE', 24)->default('');
			$table->string('USR_FAX', 24)->default('');
			$table->string('USR_CELLULAR', 24)->default('');
			$table->string('USR_ZIP_CODE', 16)->default('');
			$table->string('DEP_UID', 32)->default('');
			$table->string('USR_POSITION', 100)->default('');
			$table->string('USR_RESUME', 100)->default('');
			$table->date('USR_BIRTHDAY')->nullable();
			$table->string('USR_ROLE', 32)->nullable()->default('PROCESSMAKER_ADMIN');
			$table->string('USR_REPORTS_TO', 32)->nullable()->default('');
			$table->string('USR_REPLACED_BY', 32)->nullable()->default('');
			$table->string('USR_UX', 128)->nullable()->default('NORMAL');
			$table->decimal('USR_COST_BY_HOUR', 7)->nullable()->default(0.00);
			$table->string('USR_UNIT_COST', 50)->nullable()->default('');
			$table->string('USR_PMDRIVE_FOLDER_UID', 32)->nullable()->default('');
			$table->text('USR_BOOKMARK_START_CASES', 16777215)->nullable();
			$table->string('USR_TIME_ZONE', 100)->nullable()->default('');
			$table->string('USR_DEFAULT_LANG', 10)->nullable()->default('');
			$table->dateTime('USR_LAST_LOGIN')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('USERS');
	}

}

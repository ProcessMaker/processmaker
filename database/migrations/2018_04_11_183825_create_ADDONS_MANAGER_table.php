<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateADDONSMANAGERTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ADDONS_MANAGER', function(Blueprint $table)
		{
			$table->string('ADDON_ID', 100);
			$table->string('STORE_ID', 32);
			$table->string('ADDON_NAME');
			$table->string('ADDON_NICK');
			$table->string('ADDON_DOWNLOAD_FILENAME', 1024)->nullable();
			$table->string('ADDON_DESCRIPTION', 2048)->nullable();
			$table->string('ADDON_STATE');
			$table->dateTime('ADDON_STATE_CHANGED')->nullable();
			$table->string('ADDON_STATUS');
			$table->string('ADDON_VERSION');
			$table->string('ADDON_TYPE')->index('indexAddonsType');
			$table->string('ADDON_PUBLISHER')->nullable();
			$table->dateTime('ADDON_RELEASE_DATE')->nullable();
			$table->string('ADDON_RELEASE_TYPE')->nullable();
			$table->string('ADDON_RELEASE_NOTES')->nullable();
			$table->string('ADDON_DOWNLOAD_URL', 2048)->nullable();
			$table->float('ADDON_DOWNLOAD_PROGRESS', 10, 0)->nullable();
			$table->string('ADDON_DOWNLOAD_MD5', 32)->nullable();
			$table->primary(['ADDON_ID','STORE_ID']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ADDONS_MANAGER');
	}

}

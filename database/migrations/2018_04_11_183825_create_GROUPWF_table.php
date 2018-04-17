<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGROUPWFTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('GROUPWF', function(Blueprint $table)
		{
			$table->string('GRP_UID', 32)->primary();
			$table->text('GRP_TITLE', 16777215);
			$table->char('GRP_STATUS', 8)->default('ACTIVE');
			$table->string('GRP_LDAP_DN')->default('');
			$table->string('GRP_UX', 128)->nullable()->default('NORMAL');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('GROUPWF');
	}

}

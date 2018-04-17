<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRBACAUTHENTICATIONSOURCETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('RBAC_AUTHENTICATION_SOURCE', function(Blueprint $table)
		{
			$table->string('AUTH_SOURCE_UID', 32)->default('')->primary();
			$table->string('AUTH_SOURCE_NAME', 50)->default('');
			$table->string('AUTH_SOURCE_PROVIDER', 20)->default('');
			$table->string('AUTH_SOURCE_SERVER_NAME', 50)->default('');
			$table->integer('AUTH_SOURCE_PORT')->nullable()->default(389);
			$table->integer('AUTH_SOURCE_ENABLED_TLS')->nullable()->default(0);
			$table->string('AUTH_SOURCE_VERSION', 16)->default('3');
			$table->string('AUTH_SOURCE_BASE_DN', 128)->default('');
			$table->integer('AUTH_ANONYMOUS')->nullable()->default(0);
			$table->string('AUTH_SOURCE_SEARCH_USER', 128)->default('');
			$table->string('AUTH_SOURCE_PASSWORD', 150)->default('');
			$table->string('AUTH_SOURCE_ATTRIBUTES')->default('');
			$table->string('AUTH_SOURCE_OBJECT_CLASSES')->default('');
			$table->text('AUTH_SOURCE_DATA', 16777215)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('RBAC_AUTHENTICATION_SOURCE');
	}

}

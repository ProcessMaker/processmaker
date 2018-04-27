<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDBSOURCETable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('db_sources', function(Blueprint $table)
		{
			$table->increments('id');
			$table->uuid('uid')->unique();
			$table->unsignedInteger('process_id')->nullable()->index('indexDBSource');
			$table->string('type', 8)->default('mysql');
			$table->string('server')->nullable();
			$table->string('description')->nullable();
			$table->string('database_name');
			$table->string('username');
			$table->string('password');
			$table->integer('port')->nullable();
			$table->string('encode')->default('utf8');
			$table->enum('connection_type', ['NORMAL', 'TNS']);
			$table->string('tns')->nullable();
			$table->unique(['id','process_id']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('db_sources');
	}

}

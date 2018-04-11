<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTRANSLATIONTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('TRANSLATION', function(Blueprint $table)
		{
			$table->string('TRN_CATEGORY', 100)->default('');
			$table->string('TRN_ID', 100)->default('');
			$table->string('TRN_LANG', 10)->default('en');
			$table->text('TRN_VALUE', 16777215);
			$table->date('TRN_UPDATE_DATE')->nullable();
			$table->primary(['TRN_CATEGORY','TRN_ID','TRN_LANG']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('TRANSLATION');
	}

}

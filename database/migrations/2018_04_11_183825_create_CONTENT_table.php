<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCONTENTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CONTENT', function(Blueprint $table)
		{
			$table->string('CON_CATEGORY', 30)->default('');
			$table->string('CON_PARENT', 32)->default('');
			$table->string('CON_ID', 100)->default('');
			$table->string('CON_LANG', 10)->default('');
			$table->text('CON_VALUE', 16777215);
			$table->primary(['CON_CATEGORY','CON_PARENT','CON_ID','CON_LANG']);
			$table->index(['CON_ID','CON_LANG'], 'indexUidLang');
			$table->index(['CON_CATEGORY','CON_PARENT','CON_ID','CON_LANG'], 'indexCatParUidLang');
			$table->index(['CON_ID','CON_CATEGORY','CON_LANG'], 'indexUid');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('CONTENT');
	}

}

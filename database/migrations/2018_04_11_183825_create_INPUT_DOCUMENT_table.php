<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateINPUTDOCUMENTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('INPUT_DOCUMENT', function(Blueprint $table)
		{
			$table->string('INP_DOC_UID', 32)->default('')->primary();
			$table->string('PRO_UID', 32)->default('0');
			$table->text('INP_DOC_TITLE', 16777215);
			$table->text('INP_DOC_DESCRIPTION', 16777215)->nullable();
			$table->string('INP_DOC_FORM_NEEDED', 20)->default('REAL');
			$table->string('INP_DOC_ORIGINAL', 20)->default('COPY');
			$table->string('INP_DOC_PUBLISHED', 20)->default('PRIVATE');
			$table->boolean('INP_DOC_VERSIONING')->default(0);
			$table->text('INP_DOC_DESTINATION_PATH', 16777215)->nullable();
			$table->text('INP_DOC_TAGS', 16777215)->nullable();
			$table->string('INP_DOC_TYPE_FILE', 200)->nullable()->default('*.*');
			$table->integer('INP_DOC_MAX_FILESIZE')->default(0);
			$table->string('INP_DOC_MAX_FILESIZE_UNIT', 2)->default('KB');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('INPUT_DOCUMENT');
	}

}

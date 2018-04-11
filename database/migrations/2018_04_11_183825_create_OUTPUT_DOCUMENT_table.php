<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOUTPUTDOCUMENTTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('OUTPUT_DOCUMENT', function(Blueprint $table)
		{
			$table->string('OUT_DOC_UID', 32)->default('')->primary();
			$table->text('OUT_DOC_TITLE', 16777215);
			$table->text('OUT_DOC_DESCRIPTION', 16777215)->nullable();
			$table->text('OUT_DOC_FILENAME', 16777215)->nullable();
			$table->text('OUT_DOC_TEMPLATE', 16777215)->nullable();
			$table->string('PRO_UID', 32)->default('');
			$table->string('OUT_DOC_REPORT_GENERATOR', 10)->default('HTML2PDF');
			$table->boolean('OUT_DOC_LANDSCAPE')->default(0);
			$table->string('OUT_DOC_MEDIA', 10)->default('Letter');
			$table->integer('OUT_DOC_LEFT_MARGIN')->nullable()->default(30);
			$table->integer('OUT_DOC_RIGHT_MARGIN')->nullable()->default(15);
			$table->integer('OUT_DOC_TOP_MARGIN')->nullable()->default(15);
			$table->integer('OUT_DOC_BOTTOM_MARGIN')->nullable()->default(15);
			$table->string('OUT_DOC_GENERATE', 10)->default('BOTH');
			$table->string('OUT_DOC_TYPE', 32)->default('HTML');
			$table->integer('OUT_DOC_CURRENT_REVISION')->nullable()->default(0);
			$table->text('OUT_DOC_FIELD_MAPPING', 16777215)->nullable();
			$table->boolean('OUT_DOC_VERSIONING')->default(0);
			$table->text('OUT_DOC_DESTINATION_PATH', 16777215)->nullable();
			$table->text('OUT_DOC_TAGS', 16777215)->nullable();
			$table->boolean('OUT_DOC_PDF_SECURITY_ENABLED')->nullable()->default(0);
			$table->string('OUT_DOC_PDF_SECURITY_OPEN_PASSWORD', 32)->nullable()->default('');
			$table->string('OUT_DOC_PDF_SECURITY_OWNER_PASSWORD', 32)->nullable()->default('');
			$table->string('OUT_DOC_PDF_SECURITY_PERMISSIONS', 150)->nullable()->default('');
			$table->integer('OUT_DOC_OPEN_TYPE')->nullable()->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('OUTPUT_DOCUMENT');
	}

}

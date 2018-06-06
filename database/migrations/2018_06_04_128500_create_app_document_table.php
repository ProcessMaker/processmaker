<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppDocumentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('APP_DOCUMENT', function(Blueprint $table)
        {
            $table->string('APP_DOC_UID', 32)->default('');
            $table->text('APP_DOC_FILENAME', 16777215);
            $table->text('APP_DOC_TITLE', 16777215)->nullable();
            $table->text('APP_DOC_COMMENT', 16777215)->nullable();
            $table->integer('DOC_VERSION')->default(1);
            $table->string('APP_UID', 32)->default('')->index('indexAppUid');
            $table->integer('DEL_INDEX')->default(0);
            $table->string('DOC_UID', 32)->default('');
            $table->string('USR_UID', 32)->default('');
            $table->string('APP_DOC_TYPE', 32)->default('');
            $table->dateTime('APP_DOC_CREATE_DATE');
            $table->integer('APP_DOC_INDEX');
            $table->string('FOLDER_UID', 32)->nullable()->default('');
            $table->string('APP_DOC_PLUGIN', 150)->nullable()->default('');
            $table->text('APP_DOC_TAGS', 16777215)->nullable();
            $table->string('APP_DOC_STATUS', 32)->default('ACTIVE');
            $table->dateTime('APP_DOC_STATUS_DATE')->nullable();
            $table->string('APP_DOC_FIELDNAME', 150)->nullable();
            $table->text('APP_DOC_DRIVE_DOWNLOAD', 16777215)->nullable();
            $table->string('SYNC_WITH_DRIVE', 32)->default('UNSYNCHRONIZED');
            $table->text('SYNC_PERMISSIONS', 16777215)->nullable();
            $table->primary(['APP_DOC_UID','DOC_VERSION']);
            $table->index(['FOLDER_UID','APP_DOC_UID'], 'indexAppDocument');
            $table->index(['APP_UID','DOC_UID','DOC_VERSION','APP_DOC_TYPE'], 'indexAppUidDocUidDocVersionDocType');

            $table->unsignedInteger('application_id');
            // Setup relationship for Application we belong to
            $table->foreign('application_id')->references('id')->on('APPLICATION')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('APP_DOCUMENT');
    }
}

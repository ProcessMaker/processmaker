<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function(Blueprint $table)
        {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('application_id')->unsigned();
            $table->integer('user_id')->unsigned();
            
            $table->text('filename');
            $table->text('title')->nullable();
            $table->text('comment')->nullable();
            $table->integer('doc_version')->default(1);
            $table->integer('del_index')->default(0);
            $table->string('document_id', 32)->default('');
            $table->string('document_type', 32)->default('');
            $table->integer('index');
            $table->string('folder_uid', 32)->nullable()->default('');
            $table->string('plugin', 150)->nullable()->default('');
            $table->text('tags')->nullable();
            $table->string('status', 32)->default('ACTIVE');
            $table->dateTime('status_date')->nullable();
            $table->string('fieldname', 150)->nullable();

            $table->index(['folder_uid','uid'], 'indexAppDocument');
            $table->index(['application_id','uid','doc_version','type'], 'indexAppUidDocUidDocVersionDocType');

            $table->timestamps();

            // Setup relationship for Process we belong to
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
        Schema::dropIfExists('documents');
    }
}

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
        Schema::create('documents', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->integer('application_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('folder_id')->nullable()->unsigned();

            $table->text('filename');
            $table->text('title')->nullable();
            $table->text('comment')->nullable();
            $table->integer('version')->default(1);
            $table->integer('index')->default(0);
            $table->string('type', 32)->default('');
            $table->text('tags')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->index(['folder_id', 'id'], 'indexDocument');
            $table->index(['uid', 'folder_id', 'version', 'type'], 'indexDocVersionType');

            $table->timestamps();

            // Setup relationship for Application we belong to
            $table->foreign('application_id')->references('id')->on('APPLICATION')->onDelete('cascade');
            // Setup relationship for User we belong to
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Setup relationship for Folder we belong to
            $table->foreign('folder_id')->references('id')->on('folders')->onDelete('cascade');
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

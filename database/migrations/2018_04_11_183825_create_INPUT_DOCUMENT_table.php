<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateINPUTDOCUMENTTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('input_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->text('title', 16777215);
            $table->text('description', 16777215)->nullable();
            $table->string('form_needed', 20)->default('REAL');
            $table->string('original', 20)->default('COPY');
            $table->string('published', 20)->default('PRIVATE');
            $table->boolean('versioning')->default(0);
            $table->text('destination_path', 16777215)->nullable();
            $table->text('tags', 16777215)->nullable();
            $table->string('type_file', 200)->nullable()->default('*.*');
            $table->integer('max_filesize')->default(0);
            $table->string('max_filesize_unit', 2)->default('KB');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('input_documents');
    }

}

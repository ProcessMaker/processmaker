<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOUTPUTDOCUMENTTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('output_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->text('title');
            $table->text('description')->nullable();
            $table->text('filename')->nullable();
            $table->text('template')->nullable();
            $table->string('report_generator', 10)->default('HTML2PDF');
            $table->string('type', 32)->default('HTML');
            $table->boolean('versioning')->default(0);
            $table->integer('current_revision')->default(0);
            $table->text('tags')->nullable();
            $table->integer('open_type')->default(1);
            $table->string('generate', 10)->default('BOTH');
            $table->text('properties')->nullable();
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
        Schema::drop('output_documents');
    }

}

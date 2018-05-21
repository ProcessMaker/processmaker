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
            $table->text('template', 16777215)->nullable();
            $table->string('report_generator', 10)->default('HTML2PDF');
            $table->string('type', 32)->default('HTML');
            $table->boolean('versioning')->default(0);
            $table->integer('current_revision')->default(0);
            $table->text('tags')->nullable();
            $table->integer('open_type')->default(1);
            $table->string('generate', 10)->default('BOTH');
            $table->text('properties', 16777215)->nullable();


            $table->boolean('landscape')->default(0);
            $table->string('media', 10)->default('Letter');
            $table->integer('left_margin')->nullable()->default(30);
            $table->integer('right_margin')->nullable()->default(15);
            $table->integer('top_margin')->nullable()->default(15);
            $table->integer('bottom_margin')->nullable()->default(15);
            $table->text('field_mapping', 16777215)->nullable();
            $table->text('destination_path', 16777215)->nullable();
            $table->boolean('pdf_security_enabled')->default(0);
            $table->string('pdf_security_open_password', 256)->nullable()->default('');
            $table->string('pdf_security_owner_password', 256)->nullable()->default('');
            $table->string('pdf_security_permissions', 150)->nullable()->default('');

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

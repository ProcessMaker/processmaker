<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_variables', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid', 36);
            $table->integer('process_id')->nullable()->unsigned();
            $table->integer('input_document_id')->nullable()->unsigned();
            $table->integer('db_source_id')->nullable()->unsigned();

            $table->string('name');
            $table->string('field_type', 32)->nullable()->default('');
            $table->integer('field_size')->nullable();
            $table->string('label')->default('');
            $table->text('sql')->nullable();
            $table->boolean('null')->default(false);
            $table->string('default', 32)->default('');
            $table->text('accepted_values')->nullable('');

            $table->unique(['process_id', 'name'], 'uniqueVariableName');

            // Setup relationship for process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            // Setup relationship for process we belong to
            $table->foreign('input_document_id')->references('id')->on('input_documents')->onDelete('cascade');
            // Setup relationship for process we belong to
            $table->foreign('db_source_id')->references('id')->on('db_sources')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_variables');
    }
}

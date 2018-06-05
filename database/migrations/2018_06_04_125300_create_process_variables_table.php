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
        Schema::create('PROCESS_VARIABLES', function(Blueprint $table)
        {
            $table->increments('VAR_ID');
            $table->string('VAR_UID', 32)->unique();
            $table->unsignedInteger('PRO_ID');
            $table->string('VAR_NAME')->nullable()->default('');
            $table->string('VAR_FIELD_TYPE', 32)->nullable()->default('');
            $table->integer('VAR_FIELD_SIZE')->nullable();
            $table->string('VAR_LABEL')->nullable()->default('');
            $table->uuid('VAR_DBCONNECTION')->nullable();
            $table->text('VAR_SQL', 16777215)->nullable();
            $table->boolean('VAR_NULL')->nullable()->default(0);
            $table->string('VAR_DEFAULT', 32)->nullable()->default('');
            $table->text('VAR_ACCEPTED_VALUES', 16777215)->nullable();
            $table->string('INP_DOC_UID', 32)->nullable()->default('');
            $table->unique(['PRO_ID','VAR_NAME'], 'uniqueVariableName');

            // Setup relationship for process we belong to
            $table->foreign('PRO_ID')->references('id')->on('processes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('PROCESS_VARIABLES');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReportTableColumnsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_table_columns', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('report_table_id')->unsigned();
            $table->string('name');
            $table->string('dynaform_name')->nullable();
            $table->integer('dynaform_id')->nullable()->unsigned();
            $table->boolean('filter')->default(false);
            $table->integer('process_variable_id')->nullable()->unsigned();

            // Setup relationship for Additional tables we belong to
            $table->foreign('report_table_id')->references('id')->on('additional_tables')->onDelete('CASCADE');
            // Setup relationship for Form we belong to
            $table->foreign('dynaform_id')->references('id')->on('forms')->onDelete('cascade');
            // Setup relationship for Process Variable we belong to
            $table->foreign('process_variable_id')->references('VAR_ID')->on('PROCESS_VARIABLES')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('report_table_columns');
    }

}

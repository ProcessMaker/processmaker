<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAdditionalTablesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('additional_tables', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['PMTABLE', 'NORMAL', 'GRID'])->default('PMTABLE');
            $table->string('grid')->nullable();
            $table->string('tags')->nullable();

            $table->integer('db_source_id')->unsigned();
            $table->integer('process_id')->unsigned()->index('indexAdditionalProcess');


            // Setup relationship for Db Sources we belong to
            $table->foreign('db_source_id')->references('id')->on('db_sources')->onDelete('cascade');
            // Setup relationship for Process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('additional_tables');
    }

}

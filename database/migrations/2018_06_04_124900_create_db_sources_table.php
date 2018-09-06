<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDbSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('db_sources', function(Blueprint $table)
        {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('process_id')->nullable()->index('indexDBSource');
            $table->string('type', 8)->default('mysql');
            $table->string('server')->nullable();
            $table->string('description')->nullable();
            $table->string('database_name');
            $table->string('username');
            $table->string('password');
            $table->integer('port')->nullable();
            $table->string('encode')->default('utf8');
            $table->enum('connection_type', ['NORMAL', 'TNS'])->default('NORMAL');
            $table->string('tns')->nullable();
            $table->unique(['id','process_id']);

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
        Schema::dropIfExists('db_sources');
    }
}

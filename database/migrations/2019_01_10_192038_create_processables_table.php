<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('processables', function (Blueprint $table) {
            $table->unsignedInteger('process_id');
            $table->string('node')->nullable();
            $table->unsignedInteger('processable_id');
            $table->string('processable_type');
            $table->enum('method', ['START', 'CANCEL', 'EDIT_DATA']);
            
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
        Schema::dropIfExists('processables');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessWebEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_web_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('process_id');
            $table->string('node');
            $table->string('mode')->nullable();
            $table->string('completed_action')->default('SCREEN');
            $table->unsignedInteger('completed_screen_id')->nullable();
            $table->string('completed_url')->nullable();
            $table->timestamps();
            
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            $table->foreign('completed_screen_id')->references('id')->on('screens');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_web_entries');
    }
}

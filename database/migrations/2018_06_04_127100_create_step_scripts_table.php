<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStepScriptsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('step_scripts', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->integer('step_id')->unsigned();
            $table->integer('script_id')->unsigned();
            $table->enum('TYPE', ['AFTER', 'BEFORE'])->default('AFTER');
            $table->string('condition')->default('');
            $table->integer('position')->default(0);
            $table->index(['step_id', 'script_id', 'TYPE'], 'indexStepScripts');

            // Setup relationship for Step we belong to
            $table->foreign('step_id')->references('id')->on('steps')->onDelete('cascade');
            // Setup relationship for Script we belong to
            $table->foreign('script_id')->references('id')->on('scripts')->onDelete('cascade');

        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('step_scripts');
    }

}

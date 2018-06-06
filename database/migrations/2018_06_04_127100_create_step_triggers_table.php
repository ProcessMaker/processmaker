<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStepTriggersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('step_triggers', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('step_id')->unsigned();
            $table->integer('trigger_id')->unsigned();
            $table->string('TYPE', 20)->default('');
            $table->string('condition')->default('');
            $table->integer('position')->default(0);
            $table->index(['step_id', 'trigger_id', 'TYPE'], 'indexStepTriggers');

            // Setup relationship for Step we belong to
            $table->foreign('step_id')->references('id')->on('steps')->onDelete('cascade');
            // Setup relationship for Trigger we belong to
            $table->foreign('trigger_id')->references('id')->on('triggers')->onDelete('cascade');

        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('step_triggers');
    }

}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStepSupervisorsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('step_supervisors', function(Blueprint $table)
        {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('step_id')->unsigned();
            $table->integer('object_id')->unsigned();
            $table->string('supervisors_type', 20)->default('DYNAFORM');
            $table->integer('position')->default(0);
            $table->index(['step_id','object_id','supervisors_type'], 'indexStepSupervisors');

            // Setup relationship for Step we belong to
            $table->foreign('step_id')->references('id')->on('steps')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('step_supervisors');
    }

}

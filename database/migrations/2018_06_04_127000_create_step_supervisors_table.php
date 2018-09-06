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

        Schema::create('step_supervisors', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid', 36)->unique();
            $table->integer('step_id')->unsigned();
            //relation with form, input, output document.
            $table->integer('object_id')->unsigned();
            $table->enum('supervisors_type', ['FORM', 'INPUT', 'OUTPUT'])->default('FORM');
            $table->integer('position')->default(0);
            $table->index(['step_id', 'object_id', 'supervisors_type'], 'indexStepSupervisors');

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

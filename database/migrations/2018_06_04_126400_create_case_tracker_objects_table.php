<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCaseTrackerObjectsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('case_tracker_objects', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('case_tracker_id')->unsigned();

            $table->integer('object_id')->unsigned();
            $table->string('case_tracker_objects_type', 20)->default('DYNAFORM');

            $table->text('condition');
            $table->integer('position')->default(0);

            $table->index(['case_tracker_id', 'object_id'], 'indexCaseTrackerObject');

            // Setup relationship for Case Tracker we belong to
            $table->foreign('case_tracker_id')->references('id')->on('case_trackers')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('case_tracker_objects');
    }

}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIsoLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('iso_locations', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('uid', 5)->unique();
            $table->unsignedInteger('country_id');
            $table->string('name')->nullable();
            $table->string('normal_name')->nullable();

            // setup relationship for Country we belong to
            $table->foreign('country_id')->references('id')->on('iso_countries')->ondelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('iso_locations');
    }
}

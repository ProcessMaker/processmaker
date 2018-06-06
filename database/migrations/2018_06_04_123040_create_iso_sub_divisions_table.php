<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIsoSubDivisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('iso_sub_divisions', function(Blueprint $table)
        {
            $table->increments('id');
            $table->unsignedInteger('iso_country_id');
            $table->string('uid', 2)->unique();
            $table->string('name')->default('');

            // setup relationship for Country we belong to
            $table->foreign('iso_country_id')->references('id')->on('iso_countries')->ondelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('iso_sub_divisions');
    }
}

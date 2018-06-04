<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('PROCESS_USER', function(Blueprint $table)
        {
            $table->uuid('PU_UID')->default('')->primary();
            $table->uuid('PRO_UID');
            $table->uuid('USR_UID');
            $table->string('PU_TYPE', 20);

            // Setup relationship for process we belong to
            //$table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('PROCESS_USER');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessCollaborationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_collaborations', function (Blueprint $table) {
            //Columns
            $table->increments('id');
            $table->unsignedInteger('process_id');
            $table->timestamps();

            //Indexes
            $table->index('process_id');

            //Foreign keys
            //A process can not be deleted if it has collaborations
            $table->foreign('process_id')
                ->references('id')->on('processes')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_collaborations');
    }
}

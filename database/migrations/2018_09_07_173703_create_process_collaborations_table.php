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
            $table->uuid('uuid');
            $table->uuid('process_uuid');
            $table->timestamps();

            //Indexes
            $table->primary('uuid');
            $table->index('process_uuid');

            //Foreign keys
            //A process can not be deleted if it has collaborations
            $table->foreign('process_uuid')
                ->references('uuid')->on('processes')
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

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessRequestsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_requests', function (Blueprint $table) {
            //Columns
            $table->uuid('uuid');
            $table->uuid('process_uuid');
            $table->uuid('process_collaboration_uuid')->nullable();
            $table->string('participant_uuid', 36);
            $table->enum('status', ['ACTIVE,COMPLETED']);
            $table->json('data');
            $table->string('name');
            $table->timestamp('completed_date')->nullable();
            $table->timestamp('init_date')->nullable();
            $table->timestamps();

            //Indexes
            $table->primary('uuid');
            $table->index('process_uuid');
            $table->index('process_collaboration_uuid');
            $table->index('participant_uuid');

            //Foreign keys
            //If the collaboration is deleted the request stays without collaboration
            $table->foreign('process_collaboration_uuid')
                ->references('uuid')->on('process_collaborations')
                ->onDelete('set null');
            //A process can not be deleted if it has requests
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
        Schema::dropIfExists('process_requests');
    }
}

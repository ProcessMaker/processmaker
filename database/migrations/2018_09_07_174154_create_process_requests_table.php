<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\ProcessRequest;

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
            $table->uuid('user_uuid')->nullable();
            $table->string('participant_uuid', 36)->nullable();
            $table->enum('status', [ProcessRequest::STATUS_ACTIVE, ProcessRequest::STATUS_COMPLETED]);
            $table->json('data');
            $table->string('name');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamps();

            //Indexes
            $table->primary('uuid');
            $table->index('process_uuid');
            $table->index('user_uuid');
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
            //An user can not be deleted if it has requests
            $table->foreign('user_uuid')
                ->references('uuid')->on('users')
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

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
        $model = new ProcessRequest;
        Schema::connection($model->getConnectionName())->create('process_requests', function (Blueprint $table) {
            //Columns
            $table->increments('id');
            $table->unsignedInteger('process_id');
            $table->unsignedInteger('process_collaboration_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('parent_request_id')->nullable();
            $table->string('participant_id')->nullable();
            // The callable id is the text id of the bpmn element
            $table->string('callable_id');
            $table->enum('status', ['DRAFT', 'ACTIVE', 'COMPLETED', 'ERROR', 'CANCELED']);
            $table->json('data');
            $table->string('name');
            $table->json('errors')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamps();

            //Indexes
            $table->index('process_id');
            $table->index('user_id');
            $table->index('process_collaboration_id');
            $table->index('parent_request_id');
            $table->index('participant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $model = new ProcessRequest;
        Schema::connection($model->getConnectionName())->dropIfExists('process_requests');
    }
}

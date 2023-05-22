<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcessRequestTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_request_tokens', function (Blueprint $table) {
            // Columns
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('process_id');
            $table->unsignedInteger('process_request_id');
            $table->unsignedInteger('subprocess_request_id')->nullable();
            // Element points to a bpmn element, not another model
            $table->string('element_id');
            $table->string('element_type');
            $table->string('element_name')->nullable();
            $table->enum('status', ['ACTIVE', 'FAILING', 'COMPLETED', 'CLOSED', 'EVENT_CATCH', 'TRIGGERED', 'INCOMING'])
                    ->default('ACTIVE');
            $table->unsignedInteger('element_index')->default(0);
            $table->string('subprocess_start_event_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('due_notified')->default(false);
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('riskchanges_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('process_id');
            $table->index('process_request_id');
            $table->index('subprocess_request_id');
            $table->index('subprocess_start_event_id');
            $table->index('user_id');

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_request_tokens');
    }
}

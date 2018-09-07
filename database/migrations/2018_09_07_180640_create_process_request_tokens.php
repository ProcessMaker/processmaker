<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->uuid('uuid');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('element_uuid', 36);
            $table->string('element_type', 36);
            $table->timestamp('initiated_at')->nullable();
            $table->uuid('process_request_uuid');
            $table->timestamp('riskchanges_at')->nullable();
            $table->enum('status', ['ACTIVE', 'FAILING', 'COMPLETED', 'CLOSED', 'EVENT_CATCH']);
            $table->uuid('user_uuid')->nullable();
            $table->timestamps();

            // Indexes
            $table->primary('uuid');
            $table->index('process_request_uuid');
            $table->index('user_uuid');

            // Foreign keys
            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('cascade');
            $table->foreign('process_request_uuid')->references('uuid')->on('process_requests')->onDelete('cascade');
        });    }

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

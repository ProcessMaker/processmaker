<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webentry_routes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_segment')->unique();
            $table->json('params')->nullable();
            $table->unsignedInteger('process_id');
            $table->string('node_id');
            $table->boolean('is_task_webentry')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('first_segment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webentry_routes');
        Schema::table('process_request_tokens', function (Blueprint $table) {
            // Remove index by: user_id, status, due_at, due_notified
            $table->dropIndex(['user_id', 'status', 'due_at', 'due_notified']);
        });
    }
};

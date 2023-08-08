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
        Schema::table('process_request_tokens', function (Blueprint $table) {
            // Add index by: user_id, status, due_at, due_notified
            $table->index(['user_id', 'status', 'due_at', 'due_notified']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            // Remove index by: user_id, status, due_at, due_notified
            $table->dropIndex(['user_id', 'status', 'due_at', 'due_notified']);
        });
    }
};

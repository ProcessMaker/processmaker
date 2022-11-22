<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationsIndexToProcessRequestTokens extends Migration
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
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('process_request_tokens');
            if (array_key_exists('user_id', $indexesFound)) {
                $table->dropIndex(['user_id', 'status', 'due_at', 'due_notified']);
            }
        });
    }
}

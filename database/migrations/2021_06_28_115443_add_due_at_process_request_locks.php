<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDueAtProcessRequestLocks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('process_request_locks', function (Blueprint $table) {
            $table->datetime('due_at')->nullable();
            $table->index(['id', 'process_request_id', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_request_locks', function (Blueprint $table) {
            $table->dropColumn('due_at');
            $table->dropIndex(['id', 'process_request_id', 'due_at']);
        });
    }
}

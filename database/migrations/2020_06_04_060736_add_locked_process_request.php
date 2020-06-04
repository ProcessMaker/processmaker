<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLockedProcessRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('process_requests', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('locked_by_token_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_requests', function (Blueprint $table) {
            $table->dropColumn(['locked_at', 'locked_by_token_id']);
        });
    }
}

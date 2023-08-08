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
            $table->index('status');
            $table->index('element_type');
            $table->index(['status', 'is_self_service']);
        });
    }

    /**
     * Reverse the migrations.
     *rt mi
     * @return void
     */
    public function down()
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['element_type']);
            $table->dropIndex(['status', 'is_self_service']);
        });
    }
};

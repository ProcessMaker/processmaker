<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSelfServiceIndexToProcessRequestTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->index('is_self_service');
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
            $table->dropIndex(['is_self_service']);
        });
    }
}

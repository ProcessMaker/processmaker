<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsSelfServiceToProcessRequestTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->boolean('is_self_service')->default(false);
            $table->json('self_service_groups')->nullable();
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
            $table->dropColumn(['is_self_service','self_service_groups']);
        });
    }
}

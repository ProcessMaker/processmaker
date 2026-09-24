<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
            ALTER TABLE `process_request_tokens`
            ADD INDEX `process_request_tokens_ele_sta_self_pro_user` (`element_type`, `status`, `is_self_service`, `process_id`, `user_id`)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
            ALTER TABLE `process_request_tokens`
            DROP INDEX `process_request_tokens_ele_sta_self_pro_user`
        ');
    }
};

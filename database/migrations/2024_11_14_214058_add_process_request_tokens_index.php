<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcessRequestTokensIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('
            ALTER TABLE `process_request_tokens`
            ADD INDEX `process_request_tokens_proc_element_created_id` (`process_id` ASC, `element_id` ASC, `created_at` DESC, `id` DESC)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('
            ALTER TABLE `process_request_tokens`
            DROP INDEX `process_request_tokens_proc_element_created_id`
        ');
    }
}

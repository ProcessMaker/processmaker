<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStatusProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE processes MODIFY COLUMN status ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE'");

        DB::table('processes')
            ->where('status', 'INACTIVE')
            ->update(['status' => 'ARCHIVED']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('processes')
            ->where('status', 'ARCHIVED')
            ->update(['status' => 'INACTIVE']);

        DB::statement("ALTER TABLE processes MODIFY COLUMN status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE'");
    }
}

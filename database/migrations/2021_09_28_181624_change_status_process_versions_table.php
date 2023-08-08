<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE process_versions MODIFY COLUMN status ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE' NOT NULL");

        DB::table('process_versions')
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
        DB::table('process_versions')
            ->where('status', 'ARCHIVED')
            ->update(['status' => 'INACTIVE']);

        DB::statement("ALTER TABLE process_versions MODIFY COLUMN status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE' NOT NULL");
    }
};

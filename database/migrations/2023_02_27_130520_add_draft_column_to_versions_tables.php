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
        $tables = [
            'process_versions',
            'screen_versions',
            'script_versions',
            'script_executor_versions',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'draft')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->boolean('draft')->default(false);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tables = [
            'process_versions',
            'screen_versions',
            'script_versions',
            'script_executor_versions',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('draft');
            });
        }
    }
};

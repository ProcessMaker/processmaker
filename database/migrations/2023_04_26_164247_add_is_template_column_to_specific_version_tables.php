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
        // Add the names of the tables you want to modify here
        $tables_to_modify = ['process_versions', 'screen_versions', 'script_versions'];

        foreach ($tables_to_modify as $table_name) {
            if (Schema::hasTable($table_name)) {
                Schema::table($table_name, function (Blueprint $table) {
                    $table->boolean('is_template')->default(false);
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
        // Add the names of the tables you want to modify here
        $tables_to_modify = ['process_versions', 'screen_versions', 'script_versions'];

        foreach ($tables_to_modify as $table_name) {
            Schema::table($table_name, function (Blueprint $table) {
                $table->dropColumn('is_template');
            });
        }
    }
};

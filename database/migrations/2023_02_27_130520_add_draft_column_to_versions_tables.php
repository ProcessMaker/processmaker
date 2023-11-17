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
        if (!Schema::hasColumn('process_versions', 'draft')) {
            Schema::table('process_versions', function (Blueprint $table) {
                $table->boolean('draft')->default(false)->after('user_id');
            });
        }

        if (!Schema::hasColumn('screen_versions', 'draft')) {
            Schema::table('screen_versions', function (Blueprint $table) {
                $table->boolean('draft')->default(false)->after('screen_category_id');
            });
        }

        if (!Schema::hasColumn('script_versions', 'draft')) {
            Schema::table('script_versions', function (Blueprint $table) {
                $table->boolean('draft')->default(false)->after('script_id');
            });
        }

        if (!Schema::hasColumn('script_executor_versions', 'draft')) {
            Schema::table('script_executor_versions', function (Blueprint $table) {
                $table->boolean('draft')->default(false)->after('script_executor_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_versions', function (Blueprint $table) {
            $table->dropColumn('draft');
        });
        Schema::table('screen_versions', function (Blueprint $table) {
            $table->dropColumn('draft');
        });
        Schema::table('script_versions', function (Blueprint $table) {
            $table->dropColumn('draft');
        });
        Schema::table('script_executor_versions', function (Blueprint $table) {
            $table->dropColumn('draft');
        });
    }
};

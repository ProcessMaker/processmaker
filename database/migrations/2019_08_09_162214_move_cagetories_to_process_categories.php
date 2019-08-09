<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use phpDocumentor\Reflection\Types\Nullable;

class MoveCagetoriesToProcessCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('screens', function (Blueprint $table) {
            $table->dropForeign('screens_screen_category_id_foreign');
            $table->dropColumn('screen_category_id');
            $table->unsignedInteger('process_category_id')->nullable()->index()->after('id');
        });
        Schema::table('scripts', function (Blueprint $table) {
            $table->dropColumn('script_category_id');
            $table->unsignedInteger('process_category_id')->nullable()->index()->after('id');
        });

        Schema::table('screen_versions', function (Blueprint $table) {
            $table->dropColumn('screen_category_id');
            $table->unsignedInteger('process_category_id')->nullable()->index()->after('screen_id');
        });
        Schema::table('script_versions', function (Blueprint $table) {
            $table->dropColumn('script_category_id');
            $table->unsignedInteger('process_category_id')->nullable()->index()->after('script_id');
        });
        Schema::drop('script_categories');
        Schema::drop('screen_categories');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}

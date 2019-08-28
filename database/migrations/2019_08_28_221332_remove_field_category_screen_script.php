<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveFieldCategoryScreenScript extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scripts', function (Blueprint $table) {
            $table->dropColumn('category');

            //$table->foreign('script_category_id')->references('id')->on('script_categories')->onDelete('cascade');
        });
        Schema::table('script_versions', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('screens', function (Blueprint $table) {
            $table->dropColumn('category');

            //$table->foreign('screen_category_id')->references('id')->on('screen_categories')->onDelete('cascade');
        });
        Schema::table('screen_versions', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scripts', function (Blueprint $table) {
            //$table->dropForeign('script_category_id');
            $table->string('category', '100')->after('status')->nullable()->default(null);
        });
        Schema::table('script_versions', function (Blueprint $table) {
            $table->string('category', '100')->after('status')->nullable()->default(null);
        });
        Schema::table('screens', function (Blueprint $table) {
            //$table->dropForeign('screen_category_id');
            $table->string('category', '100')->after('status')->nullable()->default(null);
        });
        Schema::table('screen_versions', function (Blueprint $table) {
            $table->string('category', '100')->after('status')->nullable()->default(null);
        });
    }
}

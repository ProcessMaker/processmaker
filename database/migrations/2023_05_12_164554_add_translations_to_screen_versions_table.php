<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTranslationsToScreenVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('screen_versions', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('watchers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('screen_versions', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
}

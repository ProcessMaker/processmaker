<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeoutFieldToScriptVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('script_versions', function (Blueprint $table) {
            $table->unsignedSmallInteger('timeout')->after('code')->default(60);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('script_versions', function (Blueprint $table) {
            $table->dropColumn('timeout');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRetryAttemptsAndRetryWaitTimeFieldToScriptVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('script_versions', function (Blueprint $table) {
            $table->unsignedSmallInteger('retry_attempts')->after('code')->default(0);
            $table->unsignedSmallInteger('retry_wait_time')->after('code')->default(5);
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
            $table->dropColumn('retry_attempts');
            $table->dropColumn('retry_wait_time');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRetryAttemptsAndRetryWaitTimeFieldToScriptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scripts', function (Blueprint $table) {
            $table->unsignedSmallInteger('retry_attempts')->after('code')->default(60);
            $table->unsignedSmallInteger('retry_wait_time')->after('code')->default(60);
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
            $table->dropColumn('retry_attempts');
            $table->dropColumn('retry_wait_time');
        });
    }
}

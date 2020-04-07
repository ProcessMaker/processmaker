<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExecutorToScriptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scripts', function (Blueprint $table) {
            $table->unsignedInteger('script_executor_id')->nullable();
        });
        
        Schema::table('script_versions', function (Blueprint $table) {
            $table->unsignedInteger('script_executor_id')->nullable();
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
            $table->dropColumn('script_executor_id');
        });
        
        Schema::table('script_versions', function (Blueprint $table) {
            $table->dropColumn('script_executor_id');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsProcessToScriptAndScreenCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('script_categories', function (Blueprint $table) {
            $table->boolean('is_system')->after('status')->default(false);
        });
        
        Schema::table('screen_categories', function (Blueprint $table) {
            $table->boolean('is_system')->after('status')->default(false);
        });
        
        Schema::table('screens', function (Blueprint $table) {
            $table->string('key')->nullable()->default(null);
        });
        
        Schema::table('screen_versions', function (Blueprint $table) {
            $table->string('key')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {
            //
        });
    }
}

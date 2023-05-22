<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConditionalEventsProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->json('conditional_events')->nullable();
            $table->json('properties')->nullable();
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->json('conditional_events')->nullable();
            $table->json('properties')->nullable();
        });
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $table->json('token_properties')->nullable();
        });
        Schema::create('global_variables', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn(['conditional_events']);
        });
        Schema::table('process_versions', function (Blueprint $table) {
            $table->dropColumn(['conditional_events']);
        });
        Schema::dropIfExists('global_variables');
    }
}

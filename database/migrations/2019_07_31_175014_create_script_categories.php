<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScriptCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('script_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });

        Schema::table('scripts', function (Blueprint $table) {
            $table->unsignedInteger('script_category_id')->nullable();
            $table->foreign('script_category_id')->references('id')->on('script_categories')->onDelete('cascade');
        });

        Schema::table('script_versions', function (Blueprint $table) {
            $table->unsignedInteger('script_category_id')->nullable();
            $table->foreign('script_category_id')->references('id')->on('script_categories')->onDelete('cascade');
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
            $table->dropForeign(['script_category_id']);
        });

        Schema::dropIfExists('script_categories');
    }
}

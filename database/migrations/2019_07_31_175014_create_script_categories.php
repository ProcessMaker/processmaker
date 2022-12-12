<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        });

        Schema::table('script_versions', function (Blueprint $table) {
            $table->unsignedInteger('script_category_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('script_categories');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScreenVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('screen_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('screen_id');
            $table->unsignedInteger('screen_category_id')->nullable();
            $table->text('title');
            $table->text('description');
            $table->string('type', 20)->default('FORM');
            $table->json('config')->nullable();
            $table->timestamps();

            $table->foreign('screen_id')->references('id')->on('screens')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('screen_versions');
    }
}

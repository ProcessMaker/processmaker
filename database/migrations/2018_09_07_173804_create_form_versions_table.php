<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('form_id');
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('type', 20)->default('FORM');
            $table->text('content')->nullable();
            $table->timestamps();

            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_versions');
    }
}

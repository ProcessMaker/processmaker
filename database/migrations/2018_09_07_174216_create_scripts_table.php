<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScriptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scripts', function (Blueprint $table) {
            // NOTE: Remember to update ScriptVersions when updating this table
            $table->increments('id');
            $table->string('key')->unique()->nullable();
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('language', 20)->default('PHP');
            $table->text('code')->nullable();
            $table->unsignedInteger('run_as_user_id')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('run_as_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scripts');
    }
}

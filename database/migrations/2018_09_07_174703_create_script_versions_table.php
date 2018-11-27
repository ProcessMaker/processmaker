<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScriptVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('script_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('script_id');

            $table->string('key')->unique()->nullable();
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('language', 20)->default('PHP');
            $table->text('code')->nullable();
            $table->timestamps();

            $table->foreign('script_id')->references('id')->on('scripts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('script_versions');
    }
}

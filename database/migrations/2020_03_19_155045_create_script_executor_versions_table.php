<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScriptExecutorVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('script_executor_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('script_executor_id');

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('language', 20)->default('PHP');
            $table->text('config')->nullable();
            $table->timestamps();

            $table->foreign('script_executor_id')
                   ->references('id')
                   ->on('script_executors')
                   ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('script_executor_versions');
    }
}

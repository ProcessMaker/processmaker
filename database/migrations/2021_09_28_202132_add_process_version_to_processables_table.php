<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcessVersionToProcessablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processables', function (Blueprint $table) {
            $table->integer('process_version_id')
                  ->unsigned()
                  ->after('process_id')
                  ->nullable();

            $table->foreign('process_version_id')
                  ->references('id')
                  ->on('process_versions')
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
        Schema::table('processables', function (Blueprint $table) {
            $table->dropIfExists('process_version_id');
        });
    }
}

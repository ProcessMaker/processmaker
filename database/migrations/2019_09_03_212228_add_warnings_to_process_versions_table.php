<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWarningsToProcessVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('process_versions', function (Blueprint $table) {
            $table->text('warnings')->nullable();
            $table->mediumText('bpmn')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_versions', function (Blueprint $table) {
            $table->text('bpmn')->change();
            $table->dropColumn('warnings');
        });
    }
}

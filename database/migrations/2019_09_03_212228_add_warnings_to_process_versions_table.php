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
            DB::statement('ALTER TABLE process_versions CHANGE bpmn bpmn MEDIUMTEXT');
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
            DB::statement('ALTER TABLE process_versions CHANGE bpmn bpmn TEXT');
            $table->dropColumn('warnings');
        });
    }
}

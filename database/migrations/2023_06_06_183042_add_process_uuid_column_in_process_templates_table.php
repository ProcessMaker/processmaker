<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcessUuidColumnInProcessTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('process_templates', function (Blueprint $table) {
            $table->string('editing_process_uuid')->after('process_id')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_templates', function (Blueprint $table) {
            $table->dropColumn('editing_process_uuid');
        });
    }
}

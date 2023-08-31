<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\ProcessTemplates;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ProcessTemplates::where('user_id', null)
        ->where('version', null)
        ->update(['version' => '1.0.0']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ProcessTemplates::where('user_id', null)
        ->where('version', '1.0.0')
        ->update(['version' => null]);
    }
};

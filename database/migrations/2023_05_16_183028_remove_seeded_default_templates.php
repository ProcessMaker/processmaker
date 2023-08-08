<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
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
        ProcessTemplates::where('key', 'default_templates')->where('user_id', null)->delete();
        Artisan::call('processmaker:sync-default-templates');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};

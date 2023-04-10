<?php

use Database\Seeders\ProcessTemplatesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\ProcessTemplates;

class AddSeededDataToProcessTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Artisan::call('db:seed', [
        //     '--class' => ProcessTemplatesSeeder::class,
        //     '--force' => true,
        // ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // ProcessTemplates::where('key', 'default_templates')->delete();
    }
}

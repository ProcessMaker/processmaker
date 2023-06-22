<?php

use Database\Seeders\ProcessTemplatesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ProcessTemplates;

class CreateShareSalesProposal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => ProcessTemplatesSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ProcessTemplates::where('name', 'Share Sales Proposal')->where('key', 'default_templates')->delete();
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

class ScreenSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        return Screen::getScreenByKey('interstitial');
    }
}

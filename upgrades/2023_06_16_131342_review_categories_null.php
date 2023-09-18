<?php

use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class ReviewCategoriesNull extends Upgrade
{
    public $to = '4.6.2';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('category:review-category-assign');
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
}

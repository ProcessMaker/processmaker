<?php

use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddAvatarToUsersTable extends Upgrade
{
    public $to = '4.6.0-RC6';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('users:populate-avatar');
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

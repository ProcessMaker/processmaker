<?php

namespace ProcessMaker\Events\Upgrades;

use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Contracts\Database\Events\MigrationEvent;

class NoPendingUpgradeMigrations extends NoPendingMigrations implements MigrationEvent
{
    //
}

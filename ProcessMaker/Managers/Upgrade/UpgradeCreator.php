<?php

namespace ProcessMaker\Managers\Upgrade;

use Illuminate\Database\Migrations\MigrationCreator;

class UpgradeCreator extends MigrationCreator
{
    /**
     * Overrides the default migration stubs path to return the
     * upgrade migration specific stubs directory path
     *
     * @return string
     */
    public function stubPath()
    {
        return base_path('stubs/upgrades');
    }
}

<?php

use ProcessMaker\Facades\Metrics;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class ClearPrometheusMetrics extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        Metrics::clearMetrics();
    }
}

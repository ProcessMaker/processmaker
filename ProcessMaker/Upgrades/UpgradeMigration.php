<?php

namespace ProcessMaker\Upgrades;

use Illuminate\Database\Migrations\Migration;

abstract class UpgradeMigration extends Migration
{
    /**
     * The version of ProcessMaker being upgraded *to*
     *
     * @var string example: 4.2.28
     */
    protected $to = '';

    /**
     * The version of ProcessMaker being upgraded *from*
     *
     * @var string example: 4.1.23
     */
    protected $from = '';

    /**
     * Upgrade migration cannot be skipped if the pre-upgrade checks fail
     *
     * @var bool
     */
    protected $required = true;
}

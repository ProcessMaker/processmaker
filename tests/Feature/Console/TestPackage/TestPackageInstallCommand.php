<?php

namespace Tests\Feature\Console\TestPackage;

use ProcessMaker\Console\PackageInstallCommand;

class TestPackageInstallCommand extends PackageInstallCommand
{
    protected $signature = 'processmaker:test-package-install';

    protected $description = 'Test Package Install Logic';

    public function preinstall()
    {
        $this->info('PreInstall');
    }

    public function install()
    {
        $this->info('Install');
    }

    public function postinstall()
    {
        $this->info('PostInstall');
    }
}

<?php

namespace Tests\Feature\Console\TestPackage;

class TestPackageServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->commands([
            TestPackageInstallCommand::class,
        ]);
    }
}

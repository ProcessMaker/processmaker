<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class PackageInstallCommandTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();
        $this->app->register(TestPackage\TestPackageServiceProvider::class);
    }

    public function testFullInstall()
    {
        $this->artisan('processmaker:test-package-install')
            ->expectsOutput('PreInstall')
            ->expectsOutput('Install')
            ->expectsOutput('PostInstall')
            ->assertExitCode(0);
    }

    public function testOptionPreinstall()
    {
        $this->artisan('processmaker:test-package-install --preinstall')
            ->expectsOutput('PreInstall')
            ->assertExitCode(0);
    }

    public function testOptionInstall()
    {
        $this->artisan('processmaker:test-package-install --install')
            ->expectsOutput('Install')
            ->assertExitCode(0);
    }

    public function testOptionPostinstall()
    {
        $this->artisan('processmaker:test-package-install --postinstall')
            ->expectsOutput('PostInstall')
            ->assertExitCode(0);
    }
}

<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Tests\PackageTestHelper;

class AddPackageTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "processmaker:add-package-tests";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds the tests for enterprise packages to phpunit.xml';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $xmlFile = base_path('phpunit.xml');
        $composerFile = base_path('composer.json');
        $directoryExistsFn = function($dir) {
            return is_dir(base_path($dir));
        };
        (new PackageTestHelper)->addPackageTestsToPhpuntXml($xmlFile, $composerFile, $directoryExistsFn);

    }
}

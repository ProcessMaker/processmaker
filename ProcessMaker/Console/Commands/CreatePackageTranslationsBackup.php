<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use ProcessMaker\Managers\PackageManager;

class CreatePackageTranslationsBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:create-package-translations-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create backup translations of all installed packages';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // get All packages installed
        $packages = App::make(PackageManager::class)->getJsonTranslationsRegistered();

        // Create folder resources-package
        $path = app()->basePath() . '/resources-package';
        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true, true);
            $this->info('resources-package created.');
        }

        // review all packages by translations
        foreach ($packages as $pathPackage) {
            $package = explode('/src/', $pathPackage);
            $package = explode('/', $package[0]);

            File::copyDirectory($pathPackage, $path . '/lang-' . last($package));
            if (File::exists($pathPackage . '.orig')) {
                File::copyDirectory($pathPackage . '.orig', $path . '/lang.orig-' . last($package));
            }

            $this->info('Backup ' . last($package) . ' created');
        }
    }
}

<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class InstallPreCommitHooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:install-pre-commit-hooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install git hook for php-cs-fixer in core and packages.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $coreHooksPath = base_path('.git/hooks');
        if (! is_dir($coreHooksPath)) {
            // Not in a development environment
            return;
        }
        $this->addHook($coreHooksPath);

        $finder = new Finder();
        try {
            $finder
                ->in(base_path('vendor/processmaker/*/.git'))
                ->followLinks()
                ->name('hooks');
        } catch (DirectoryNotFoundException $e) {
            // No local development packages
            return;
        }

        foreach ($finder as $dir) {
            $path = $dir->getPathname();
            $this->addHook($path);
            $this->addConfig($path.'/../../');
        }

        $this->info('Pre-commit hook installed');
    }

    private function addHook($hookDir)
    {
        $hookPath = $hookDir.'/pre-commit';
        if (file_exists($hookPath)) {
            unlink($hookPath);
        }

        symlink(
            base_path('.pre-commit'),
            $hookPath
        );
    }

    private function addConfig($configDir)
    {
        $configPath = $configDir.'/.php-cs-fixer.php';
        if (file_exists($configPath)) {
            unlink($configPath);
        }
        symlink(
            base_path('.php-cs-fixer.php'),
            $configPath
        );
    }
}

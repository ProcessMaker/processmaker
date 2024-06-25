<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\ScriptRunners\Base;

class DockerExecutorPhpNayra extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docker-executor-php-nayra:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Docker Executor PHP Nayra';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $exists = ScriptExecutor::where('language', Base::NAYRA_LANG)->exists();
        if (!$exists) {
            ScriptExecutor::install([
                'language' => Base::NAYRA_LANG,
                'title' => 'Nayra (µService)',
                'description' => 'Nayra (µService) Executor',
                'config' => 'RUN composer require nayra/nayra',
            ]);
        }

        // Build the instance image. This is the same as if you were to build it from the admin UI
        Artisan::call('processmaker:build-script-executor ' . Base::NAYRA_LANG . ' --rebuild');
    }
}

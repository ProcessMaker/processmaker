<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DataSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spark:install-data-database-schema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Data Spark';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info(__('The Processmaker Spark data tables will be installed.'));

        if ($this->confirm(__('Are you ready to begin?'))) {

            $migrations = [
                'database/migrations/2019_01_14_201209_create_comments_table.php',
                'database/migrations/2018_09_07_174154_create_process_requests_table.php'
            ];

            foreach ($migrations as $path) {
                $fileName = explode('/', $path);
                $fileName = substr($fileName[2], 0, -4);
                $this->warn(__('Migrating:') . ' ' . $fileName);

                // Run the migrations that create the tables
                Artisan::call('migrate:refresh',
                    [
                        '--path' => $path,
                        '--force' => true
                    ]
                );
                $this->info(__('Migrated:') . '  '. $fileName);
            }

            $this->info(__('ProcessMaker Spark data tables installed successfully.'));
        }

        return true;
    }
}

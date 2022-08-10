<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Database\Console\Migrations\FreshCommand;

class MigrateFresh extends FreshCommand
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $databases = ['data', 'processmaker'];

        foreach ($databases as $database) {
            if ($this->option('drop-views')) {
                $this->call('db:wipe', array_filter([
                    '--database' => $database,
                    '--drop-views' => true,
                    '--drop-types' => true,
                    '--force' => true,
                ]));
                $this->info('Dropped all views successfully.');
            }
        }

        $this->info('Dropped all tables successfully.');

        $this->call('migrate', array_filter([
            '--database' => $database,
            '--path' => $this->input->getOption('path'),
            '--realpath' => $this->input->getOption('realpath'),
            '--force' => true,
            '--step' => $this->option('step'),
        ]));

        if ($this->needsSeeding()) {
            $this->runSeeder($database);
        }
    }
}

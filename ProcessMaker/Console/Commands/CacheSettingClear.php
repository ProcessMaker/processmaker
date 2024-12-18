<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;

class CacheSettingClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:settings-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all of items from the settings cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \SettingCache::clear();

        $this->info('Settings cache cleared.');
    }
}

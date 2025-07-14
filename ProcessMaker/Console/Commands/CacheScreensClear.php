<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;

class CacheScreensClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:screens-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the cache for screens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $screenCache = ScreenCacheFactory::getScreenCache();
        $screenCache->clearCompiledAssets();
        $this->info('Cache for screens cleared');
    }
}

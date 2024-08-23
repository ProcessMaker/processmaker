<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use ProcessMaker\Jobs\BuildScriptExecutor;

class UnlockExecutorBuilder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:unlock-executor-builder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to unlock the executor builder';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $job = new BuildScriptExecutor('', '');
        $keyLock = $job->middleware()[0]->getLockKey($job);

        // release lock from cache
        $cache = Container::getInstance()->make(Cache::class);
        $cache->forget($keyLock);

        return 0;
    }
}

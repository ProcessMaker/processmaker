<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Cache\Monitoring\RedisMetricsManager;

class CacheMetricsClearCommand extends Command
{
    protected $signature = 'cache:metrics-clear';

    protected $description = 'Clear all cache metrics data';

    protected RedisMetricsManager $metrics;

    public function __construct(RedisMetricsManager $metrics)
    {
        parent::__construct();
        $this->metrics = $metrics;
    }

    public function handle()
    {
        if (!$this->confirm('Are you sure you want to clear all cache metrics? This action cannot be undone.')) {
            $this->info('Operation cancelled.');

            return 0;
        }
        $this->info('Clearing all cache metrics data...');
        $this->metrics->resetMetrics();
        $this->info('Cache metrics data cleared successfully!');
    }
}

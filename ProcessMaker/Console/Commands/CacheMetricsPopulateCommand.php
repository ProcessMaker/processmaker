<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Cache\Monitoring\RedisMetricsManager;

class CacheMetricsPopulateCommand extends Command
{
    protected $signature = 'cache:metrics-populate
                          {--keys=10 : Number of keys to generate}
                          {--type=both : Type of keys to generate (legacy, new, both)}';

    protected $description = 'Populate fake cache metrics data for testing';

    protected RedisMetricsManager $metrics;

    public function __construct(RedisMetricsManager $metrics)
    {
        parent::__construct();
        $this->metrics = $metrics;
    }

    public function handle()
    {
        $numKeys = (int) $this->option('keys');
        $type = $this->option('type');

        $this->info("Populating {$numKeys} fake cache metrics...");

        // Reset existing metrics
        $this->metrics->resetMetrics();

        // Generate keys based on type
        $keys = $this->generateKeys($numKeys, $type);

        // Populate metrics for each key
        foreach ($keys as $key) {
            $this->populateKeyMetrics($key);
            $this->output->write('.');
        }

        $this->newLine();
        $this->info('Done! You can now run cache:metrics to see the data.');
    }

    protected function generateKeys(int $count, string $type): array
    {
        $keys = [];
        $prefixes = $this->getPrefixes($type);

        for ($i = 0; $i < $count; $i++) {
            $prefix = $prefixes[array_rand($prefixes)];
            $id = rand(1, 1000);

            if ($prefix === 'screen_') {
                $keys[] = "screen_{$id}_version_" . rand(1, 5);
            } else {
                $keys[] = "pid_{$id}_{$id}_en_sid_{$id}_" . rand(1, 5);
            }
        }

        return $keys;
    }

    protected function getPrefixes(string $type): array
    {
        return match ($type) {
            'legacy' => ['screen_'],
            'new' => ['pid_'],
            default => ['screen_', 'pid_'],
        };
    }

    protected function populateKeyMetrics(string $key): void
    {
        // Generate random number of hits (0-1000)
        $hits = rand(0, 1000);

        // Generate random number of misses (0-200)
        $misses = rand(0, 200);

        // Record hits
        for ($i = 0; $i < $hits; $i++) {
            $time = $this->generateHitTime();
            $this->metrics->recordHit($key, $time);
        }

        // Record misses
        for ($i = 0; $i < $misses; $i++) {
            $time = $this->generateMissTime();
            $this->metrics->recordMiss($key, $time);
        }

        // Record memory usage (10KB - 1MB)
        $size = rand(10 * 1024, 1024 * 1024);
        $this->metrics->recordWrite($key, $size);
    }

    protected function generateHitTime(): float
    {
        // Generate hit time between 0.001 and 0.1 seconds
        return rand(1000, 100000) / 1000000;
    }

    protected function generateMissTime(): float
    {
        // Generate miss time between 0.1 and 1 second
        return rand(100000, 1000000) / 1000000;
    }
}

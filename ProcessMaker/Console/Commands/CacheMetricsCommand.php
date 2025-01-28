<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Cache\Monitoring\RedisMetricsManager;
use Symfony\Component\Console\Helper\Table;

class CacheMetricsCommand extends Command
{
    protected $signature = 'cache:metrics
                          {--key= : Show metrics for a specific cache key}
                          {--type= : Filter by cache type (legacy, new)}
                          {--format=table : Output format (table, json)}';

    protected $description = 'Display cache metrics and performance statistics';

    protected RedisMetricsManager $metrics;

    public function __construct(RedisMetricsManager $metrics)
    {
        parent::__construct();
        $this->metrics = $metrics;
    }

    public function handle()
    {
        $key = $this->option('key');
        $type = $this->option('type');
        $format = $this->option('format');

        if ($key) {
            $this->displayKeyMetrics($key, $format);
        } else {
            $this->displaySummary($type, $format);
        }

        return 0;
    }

    protected function displayKeyMetrics(string $key, string $format): void
    {
        $hitRate = $this->metrics->getHitRate($key);
        $missRate = $this->metrics->getMissRate($key);
        $avgHitTime = $this->metrics->getHitAvgTime($key);
        $avgMissTime = $this->metrics->getMissAvgTime($key);
        $memory = $this->metrics->getMemoryUsage($key);

        $data = [
            'key' => $key,
            'hit_rate' => $hitRate,
            'miss_rate' => $missRate,
            'avg_hit_time' => $avgHitTime,
            'avg_miss_time' => $avgMissTime,
            'memory_usage' => $memory,
        ];

        if ($format === 'json') {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));

            return;
        }

        $this->info("Cache Metrics for key: {$key}");
        $this->newLine();

        $table = new Table($this->output);
        $table->setHeaders(['Metric', 'Value']);
        $table->setRows([
            ['Hit Rate', $this->formatPercentage($hitRate)],
            ['Miss Rate', $this->formatPercentage($missRate)],
            ['Avg Hit Time', sprintf('%.4f sec', $avgHitTime)],
            ['Avg Miss Time', sprintf('%.4f sec', $avgMissTime)],
            ['Memory Usage', $this->formatBytes($memory['current_size'])],
            ['Last Write', $memory['last_write'] ? date('Y-m-d H:i:s', $memory['last_write']) : 'Never'],
        ]);
        $table->render();
    }

    protected function displaySummary(?string $type, string $format): void
    {
        $summary = $this->metrics->getSummary();

        if ($type) {
            $summary = $this->filterByType($summary, $type);
        }

        if ($format === 'json') {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT));

            return;
        }

        $this->info('Cache Performance Summary');
        $this->newLine();

        // Overall Statistics
        $table = new Table($this->output);
        $table->setHeaders(['Metric', 'Value']);
        $table->setRows([
            ['Total Keys', number_format($summary['total_keys'])],
            ['Overall Hit Ratio', $this->formatPercentage($summary['overall_hit_ratio'])],
            ['Overall Miss Ratio', $this->formatPercentage($summary['overall_miss_ratio'])],
            ['Average Hit Time', sprintf('%.4f sec', $summary['avg_hit_time'])],
            ['Average Miss Time', sprintf('%.4f sec', $summary['avg_miss_time'])],
            ['Total Memory Usage', $this->formatBytes($summary['total_memory_usage'])],
        ]);
        $table->render();

        // Display key details if there are any
        if (!empty($summary['keys'])) {
            $this->newLine();
            $this->info('Key Details');
            $this->newLine();

            $table = new Table($this->output);
            $table->setHeaders([
                'Key',
                'Hits',
                'Hit Ratio',
                'Misses',
                'Miss Ratio',
                'Avg Time',
                'Memory',
                'Status',
            ]);

            foreach ($summary['keys'] as $key => $metrics) {
                $table->addRow([
                    $key,
                    number_format($metrics['hits']),
                    $this->formatPercentage($metrics['hit_ratio']),
                    number_format($metrics['misses']),
                    $this->formatPercentage($metrics['miss_ratio']),
                    sprintf('%.4f sec', $metrics['avg_hit_time']),
                    $this->formatBytes($metrics['memory_usage']),
                    $this->getPerformanceStatus($metrics['hit_ratio']),
                ]);
            }

            $table->render();
        }
    }

    protected function filterByType(array $summary, string $type): array
    {
        $filtered = [
            'keys' => [],
            'total_keys' => 0,
            'overall_hit_ratio' => 0,
            'overall_miss_ratio' => 0,
            'avg_hit_time' => 0,
            'avg_miss_time' => 0,
            'total_memory_usage' => 0,
        ];

        foreach ($summary['keys'] as $key => $metrics) {
            if ($this->getKeyType($key) === $type) {
                $filtered['keys'][$key] = $metrics;
                $filtered['total_keys']++;
                $filtered['total_memory_usage'] += $metrics['memory_usage'];
                $filtered['avg_hit_time'] += $metrics['avg_hit_time'];
                $filtered['avg_miss_time'] += $metrics['avg_miss_time'];
            }
        }

        if ($filtered['total_keys'] > 0) {
            $filtered['avg_hit_time'] /= $filtered['total_keys'];
            $filtered['avg_miss_time'] /= $filtered['total_keys'];
        }

        return $filtered;
    }

    protected function getKeyType(string $key): string
    {
        if (str_starts_with($key, 'screen_')) {
            return 'legacy';
        }
        if (str_starts_with($key, 'pid_')) {
            return 'new';
        }

        return 'unknown';
    }

    protected function formatPercentage(float $value): string
    {
        return sprintf('%.2f%%', $value * 100);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return sprintf('%.2f %s', $bytes, $units[$pow]);
    }

    protected function getPerformanceStatus(float $hitRatio): string
    {
        if ($hitRatio >= 0.95) {
            return '✅ Excellent';
        }
        if ($hitRatio >= 0.80) {
            return '✓ Good';
        }
        if ($hitRatio >= 0.60) {
            return '⚠️ Fair';
        }

        return '❌ Poor';
    }
}

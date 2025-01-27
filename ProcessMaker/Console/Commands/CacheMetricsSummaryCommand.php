<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Cache\Monitoring\RedisMetricsManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

class CacheMetricsSummaryCommand extends Command
{
    protected $signature = 'cache:metrics-summary
                          {--days=1 : Number of days to analyze}
                          {--type= : Filter by cache type (legacy, new)}
                          {--format=table : Output format (table, json)}';

    protected $description = 'Generate a summary of cache performance metrics';

    protected RedisMetricsManager $metrics;

    public function __construct(RedisMetricsManager $metrics)
    {
        parent::__construct();
        $this->metrics = $metrics;
    }

    public function handle()
    {
        $days = (int) $this->option('days');
        $type = $this->option('type');
        $format = $this->option('format');

        $summary = $this->metrics->getSummary();
        $topKeys = $this->metrics->getTopKeys(10);

        if ($format === 'json') {
            $this->outputJson($summary, $topKeys);

            return;
        }

        $this->outputTable($summary, $topKeys);
    }

    protected function outputJson(array $summary, array $topKeys): void
    {
        $data = [
            'summary' => $summary,
            'top_keys' => $topKeys,
            'generated_at' => now()->toIso8601String(),
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function outputTable(array $summary, array $topKeys): void
    {
        // Overall Statistics
        $this->info('Cache Performance Summary');
        $this->newLine();

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

        // Performance Insights
        $this->newLine();
        $this->info('Performance Insights');
        $this->newLine();

        $insights = $this->generateInsights($summary);
        foreach ($insights as $insight) {
            $this->line(" • {$insight}");
        }

        // Top Keys
        $this->newLine();
        $this->info('Top 10 Most Accessed Keys');
        $this->newLine();

        $table = new Table($this->output);
        $table->setHeaders([
            'Key',
            'Hits',
            'Hit Ratio',
            'Avg Time',
            'Memory',
            'Status',
        ]);

        foreach ($topKeys as $key => $metrics) {
            $table->addRow([
                $key,
                number_format($metrics['hits']),
                $this->formatPercentage($metrics['hit_ratio']),
                sprintf('%.4f sec', $metrics['avg_hit_time']),
                $this->formatBytes($metrics['memory_usage']),
                $this->getPerformanceStatus($metrics['hit_ratio']),
            ]);
        }

        $table->render();
    }

    protected function generateInsights(array $summary): array
    {
        $insights = [];

        // Hit ratio insights
        if ($summary['overall_hit_ratio'] < 0.5) {
            $insights[] = 'Low hit ratio indicates potential cache configuration issues';
        } elseif ($summary['overall_hit_ratio'] > 0.9) {
            $insights[] = 'Excellent hit ratio shows effective cache utilization';
        }

        // Response time insights
        if ($summary['avg_miss_time'] > $summary['avg_hit_time'] * 10) {
            $insights[] = 'High miss penalty suggests optimization opportunities';
        }

        // Memory usage insights
        $avgMemoryPerKey = $summary['total_memory_usage'] / ($summary['total_keys'] ?: 1);
        if ($avgMemoryPerKey > 1024 * 1024) { // 1MB per key
            $insights[] = 'High average memory usage per key';
        }

        return $insights;
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

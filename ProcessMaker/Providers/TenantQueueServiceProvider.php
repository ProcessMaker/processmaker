<?php

namespace ProcessMaker\Providers;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;

class TenantQueueServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerQueueEventListeners();
    }

    /**
     * Register queue event listeners to track jobs by tenant.
     */
    protected function registerQueueEventListeners(): void
    {
        // Job processing
        Queue::before(function (JobProcessing $event) {
            $this->trackJobByTenant($event->job, 'processing');
        });

        // Job processed
        Queue::after(function (JobProcessed $event) {
            $this->trackJobByTenant($event->job, 'completed');
        });

        // Job failed
        Queue::failing(function (JobFailed $event) {
            $this->trackJobByTenant($event->job, 'failed');
        });

        // Job exception occurred
        Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
            $this->trackJobByTenant($event->job, 'exception');
        });
    }

    /**
     * Track job by tenant in Redis.
     */
    protected function trackJobByTenant($job, string $status): void
    {
        try {
            $tenantId = $this->extractTenantIdFromJob($job);

            if (!$tenantId) {
                return;
            }

            $jobId = $job->getJobId();
            if (!$jobId) {
                return;
            }

            $jobData = [
                'id' => $jobId,
                'name' => $this->getJobName($job),
                'queue' => $job->getQueue() ?? 'default',
                'status' => $status,
                'timestamp' => now()->timestamp,
                'tenant_id' => $tenantId,
                'attempts' => $job->attempts(),
            ];

            // Store job data with tenant prefix
            $tenantKey = "tenant_jobs:{$tenantId}:{$jobId}";
            Redis::setex($tenantKey, 86400, json_encode($jobData)); // Expire in 24 hours

            // Update tenant job counters
            $this->updateTenantJobCounters($tenantId, $status);

            // Store in tenant-specific job lists
            $this->updateTenantJobLists($tenantId, $jobId, $status);

            Log::info('Tenant job tracked', [
                'tenant_id' => $tenantId,
                'job_id' => $jobId,
                'status' => $status,
                'job_name' => $jobData['name'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error tracking tenant job', [
                'error' => $e->getMessage(),
                'status' => $status,
            ]);
        }
    }

    /**
     * Extract tenant ID from job.
     */
    protected function extractTenantIdFromJob($job): ?string
    {
        try {
            // Try to get current tenant from context
            if (app()->bound('currentTenant')) {
                $currentTenant = app('currentTenant');
                if ($currentTenant) {
                    return $currentTenant->id;
                }
            }

            // Try to extract from job payload
            $payload = $job->payload();
            if (isset($payload['tenantId'])) {
                return $payload['tenantId'];
            }

            // Check for tenant ID in the job data
            if (isset($payload['data']['tenantId'])) {
                return $payload['data']['tenantId'];
            }

            // Check for tenant ID in the job command
            if (isset($payload['data']['command'])) {
                $command = $payload['data']['command'];
                if (preg_match('/"tenantId":"([^"]+)"/', $command, $matches)) {
                    return $matches[1];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Could not extract tenant ID from job', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get job name from job object.
     */
    protected function getJobName($job): string
    {
        try {
            $payload = $job->payload();

            return $payload['displayName'] ?? $payload['job'] ?? 'Unknown Job';
        } catch (\Exception $e) {
            return 'Unknown Job';
        }
    }

    /**
     * Update tenant job counters.
     */
    protected function updateTenantJobCounters(string $tenantId, string $status): void
    {
        $counterKey = "tenant_job_counters:{$tenantId}";

        Redis::pipeline(function ($pipe) use ($counterKey, $status) {
            $pipe->hincrby($counterKey, $status, 1);
            $pipe->hincrby($counterKey, 'total', 1);
            $pipe->expire($counterKey, 86400); // Expire in 24 hours
        });
    }

    /**
     * Update tenant job lists.
     */
    protected function updateTenantJobLists(string $tenantId, string $jobId, string $status): void
    {
        $listKey = "tenant_job_lists:{$tenantId}:{$status}";

        Redis::pipeline(function ($pipe) use ($listKey, $jobId) {
            $pipe->lpush($listKey, $jobId);
            $pipe->ltrim($listKey, 0, 999); // Keep only last 1000 jobs
            $pipe->expire($listKey, 86400); // Expire in 24 hours
        });
    }

    /**
     * Get jobs for a specific tenant.
     */
    public static function getTenantJobs(string $tenantId, string $status = null, int $limit = 50): array
    {
        $jobs = [];

        if ($status) {
            $listKey = "tenant_job_lists:{$tenantId}:{$status}";
            $jobIds = Redis::lrange($listKey, 0, $limit - 1);
        } else {
            // Get jobs from all statuses
            $statuses = ['processing', 'completed', 'failed', 'exception'];
            $jobIds = [];

            foreach ($statuses as $status) {
                $listKey = "tenant_job_lists:{$tenantId}:{$status}";
                $statusJobIds = Redis::lrange($listKey, 0, intval($limit / count($statuses)));
                $jobIds = array_merge($jobIds, $statusJobIds);
            }

            $jobIds = array_slice($jobIds, 0, $limit);
        }

        foreach ($jobIds as $jobId) {
            $tenantKey = "tenant_jobs:{$tenantId}:{$jobId}";
            $jobData = Redis::get($tenantKey);

            if ($jobData) {
                $jobs[] = json_decode($jobData, true);
            }
        }

        // Sort by timestamp descending
        usort($jobs, function ($a, $b) {
            return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
        });

        return $jobs;
    }

    /**
     * Get tenant job statistics.
     */
    public static function getTenantJobStats(string $tenantId): array
    {
        $counterKey = "tenant_job_counters:{$tenantId}";
        $counters = Redis::hgetall($counterKey);

        return [
            'total' => (int) ($counters['total'] ?? 0),
            'processing' => (int) ($counters['processing'] ?? 0),
            'completed' => (int) ($counters['completed'] ?? 0),
            'failed' => (int) ($counters['failed'] ?? 0),
            'exception' => (int) ($counters['exception'] ?? 0),
        ];
    }

    /**
     * Get all tenants with job activity.
     */
    public static function getTenantsWithJobs(): array
    {
        $pattern = 'tenant_job_counters:*';
        $keys = Redis::keys($pattern);

        $tenants = [];
        foreach ($keys as $key) {
            $tenantId = str_replace('tenant_job_counters:', '', $key);
            $stats = self::getTenantJobStats($tenantId);

            if ($stats['total'] > 0) {
                $tenants[] = [
                    'id' => $tenantId,
                    'stats' => $stats,
                ];
            }
        }

        return $tenants;
    }
}

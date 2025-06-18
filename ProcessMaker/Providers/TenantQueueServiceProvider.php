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
        // Job pending
        Queue::before(function (JobProcessing $event) {
            $this->trackJobByTenant($event->job, 'pending');
        });

        // Job completed
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

            $payload = $job->payload();

            $jobData = [
                'id' => $jobId,
                'name' => $this->getJobName($job),
                'queue' => $job->getQueue() ?? 'default',
                'status' => $status,
                'created_at' => $payload['pushedAt'],
                'completed_at' => $status === 'completed' ? str_replace(',', '.', microtime(true)) : null,
                'failed_at' => $status === 'failed' ? str_replace(',', '.', microtime(true)) : null,
                'updated_at' => str_replace(',', '.', microtime(true)),
                'tenant_id' => $tenantId,
                'attempts' => $job->attempts(),
                'payload' => json_encode($payload['data']),
            ];

            if ($status === 'pending') {
                $jobData['queued_at'] = str_replace(',', '.', microtime(true));
            }

            // Check if job already exists
            $tenantKey = "tenant_jobs:{$tenantId}:{$jobId}";
            $existingJobData = Redis::get($tenantKey);

            if ($existingJobData) {
                $existingJob = json_decode($existingJobData, true);

                // Remove job from old status list if status is different
                if ($existingJob['status'] !== $status) {
                    $this->removeJobFromStatusList($tenantId, $jobId, $existingJob['status']);
                }
            }

            // Store job data with tenant prefix (always create new entry)
            Redis::hmset($tenantKey, $jobData);
            Redis::expire($tenantKey, 86400); // Expire in 24 hours

            // Update tenant job counters
            $this->updateTenantJobCounters($tenantId, $status, $jobId);

            if ($status === 'completed' || $status === 'exception' || $status === 'failed') {
                $this->removeJobFromStatusList($tenantId, $jobId, 'pending');
            }

            // Store in tenant-specific job lists
            $this->updateTenantJobLists($tenantId, $jobId, $status);

            Log::info('Tenant job tracked', [
                'tenant_id' => $tenantId,
                'job_id' => $jobId,
                'status' => $status,
                'job_name' => $jobData['name'],
                'action' => $existingJobData ? 'status_updated' : 'new_job',
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
    protected function updateTenantJobCounters(string $tenantId, string $status, string $jobId): void
    {
        $counterKey = "tenant_job_counters:{$tenantId}";

        // Check if this job ID has already been counted for this status
        $countedJobsKey = "tenant_job_counted:{$tenantId}:{$status}";
        $alreadyCounted = Redis::sismember($countedJobsKey, $jobId);

        if (!$alreadyCounted) {
            Redis::pipeline(function ($pipe) use ($counterKey, $status, $countedJobsKey, $jobId) {
                // Increment the counter
                $pipe->hincrby($counterKey, $status, 1);
                $pipe->hincrby($counterKey, 'total', 1);
                $pipe->expire($counterKey, 86400); // Expire in 24 hours

                // Mark this job ID as counted for this status
                $pipe->sadd($countedJobsKey, $jobId);
                $pipe->expire($countedJobsKey, 86400); // Expire in 24 hours
            });
        }

        // Handle status transitions: if job was previously in a different status,
        // we need to decrement the previous status counter
        $this->handleStatusTransition($tenantId, $jobId, $status);
    }

    /**
     * Handle job status transitions to maintain accurate counters.
     */
    protected function handleStatusTransition(string $tenantId, string $jobId, string $newStatus): void
    {
        $statuses = ['pending', 'completed', 'failed', 'exception'];

        foreach ($statuses as $status) {
            if ($status === $newStatus) {
                continue; // Skip the new status
            }

            $countedJobsKey = "tenant_job_counted:{$tenantId}:{$status}";
            $wasCountedInStatus = Redis::sismember($countedJobsKey, $jobId);

            if ($wasCountedInStatus) {
                // This job was previously counted in a different status
                // Remove it from the previous status and decrement the counter
                Redis::pipeline(function ($pipe) use ($tenantId, $status, $jobId, $countedJobsKey) {
                    $counterKey = "tenant_job_counters:{$tenantId}";

                    // Decrement the previous status counter
                    $pipe->hincrby($counterKey, $status, -1);
                    $pipe->hincrby($counterKey, 'total', -1);

                    // Remove the job ID from the counted set for the previous status
                    $pipe->srem($countedJobsKey, $jobId);
                });
            }
        }
    }

    /**
     * Update tenant job lists.
     */
    protected function updateTenantJobLists(string $tenantId, string $jobId, string $status): void
    {
        $listKey = "tenant_job_lists:{$tenantId}:{$status}";

        // Check if this job ID is already in the list
        $jobIds = Redis::lrange($listKey, 0, -1);

        if (!in_array($jobId, $jobIds)) {
            Redis::pipeline(function ($pipe) use ($listKey, $jobId) {
                $pipe->lpush($listKey, $jobId);
                $pipe->ltrim($listKey, 0, 999); // Keep only last 1000 jobs
                $pipe->expire($listKey, 86400); // Expire in 24 hours
            });
        } else {
            // If job is already in the list, move it to the front (most recent)
            Redis::pipeline(function ($pipe) use ($listKey, $jobId) {
                $pipe->lrem($listKey, 0, $jobId);
                $pipe->lpush($listKey, $jobId);
                $pipe->expire($listKey, 86400); // Expire in 24 hours
            });
        }
    }

    /**
     * Remove job from a specific status list.
     */
    protected function removeJobFromStatusList(string $tenantId, string $jobId, string $status): void
    {
        $listKey = "tenant_job_lists:{$tenantId}:{$status}";

        // Remove the job ID from the list
        Redis::lrem($listKey, 0, $jobId);
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
            $statuses = ['pending', 'completed', 'failed', 'exception'];
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
            $jobData = Redis::hgetall($tenantKey);

            if ($jobData) {
                $jobs[] = $jobData;
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
            'pending' => (int) ($counters['pending'] ?? 0),
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

<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Multitenancy\Tenant;
use ProcessMaker\Providers\TenantQueueServiceProvider;
use ReflectionClass;

class TenantQueueController extends Controller
{
    /**
     * Show the tenant jobs dashboard.
     */
    public function index()
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException();
        }

        return view('admin.tenant-queues.index');
    }

    /**
     * Get all tenants with job activity.
     */
    public function getTenants(): JsonResponse
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException();
        }

        $tenantsWithJobs = TenantQueueServiceProvider::getTenantsWithJobs();

        // Enrich with tenant information
        $tenants = [];
        foreach ($tenantsWithJobs as $tenantData) {
            $tenant = Tenant::find($tenantData['id']);
            if ($tenant) {
                $tenants[] = [
                    'id' => $tenant->id,
                    'name' => $tenant->name ?? "Tenant {$tenant->id}",
                    'domain' => $tenant->domain ?? 'N/A',
                    'stats' => $tenantData['stats'],
                ];
            }
        }

        return response()->json($tenants);
    }

    /**
     * Get jobs for a specific tenant.
     */
    public function getTenantJobs(Request $request, string $tenantId): JsonResponse
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException();
        }

        $status = $request->get('status');
        $limit = min((int) $request->get('limit', 50), 100); // Max 100 jobs

        $jobs = TenantQueueServiceProvider::getTenantJobs($tenantId, $status, $limit);

        return response()->json([
            'tenant_id' => $tenantId,
            'jobs' => $jobs,
            'total' => count($jobs),
        ]);
    }

    /**
     * Get job statistics for a specific tenant.
     */
    public function getTenantStats(string $tenantId): JsonResponse
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException();
        }

        $stats = TenantQueueServiceProvider::getTenantJobStats($tenantId);

        return response()->json([
            'tenant_id' => $tenantId,
            'stats' => $stats,
        ]);
    }

    /**
     * Get overall tenant job statistics.
     */
    public function getOverallStats(): JsonResponse
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException();
        }

        $tenantsWithJobs = TenantQueueServiceProvider::getTenantsWithJobs();

        $overallStats = [
            'total_tenants' => count($tenantsWithJobs),
            'total_jobs' => 0,
            'total_pending' => 0,
            'total_completed' => 0,
            'total_failed' => 0,
            'total_exception' => 0,
        ];

        foreach ($tenantsWithJobs as $tenantData) {
            $stats = $tenantData['stats'];
            $overallStats['total_jobs'] += $stats['total'];
            $overallStats['total_pending'] += $stats['pending'];
            $overallStats['total_completed'] += $stats['completed'];
            $overallStats['total_failed'] += $stats['failed'];
            $overallStats['total_exception'] += $stats['exception'];
        }

        return response()->json($overallStats);
    }

    /**
     * Get job details for a specific job.
     */
    public function getJobDetails(string $tenantId, string $jobId): JsonResponse
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException();
        }

        $tenantKey = "tenant_jobs:{$tenantId}:{$jobId}";
        $jobData = Redis::hgetall($tenantKey);

        if (!$jobData) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $jobData['payload'] = json_decode($jobData['payload'], true);

        if (isset($jobData['payload']['command'])) {
            $command = unserialize($jobData['payload']['command']);
            $reflection = new ReflectionClass($command);
            $properties = [];

            foreach ($reflection->getProperties() as $property) {
                $property->setAccessible(true);
                $properties[$property->getName()] = $property->getValue($command);
            }

            $jobData['payload']['data'] = $properties;

            return response()->json($jobData);
        }

        return response()->json($jobData);
    }

    /**
     * Clear job data for a specific tenant.
     */
    public function clearTenantJobs(string $tenantId): JsonResponse
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException();
        }

        try {
            $pattern = "tenant_jobs:{$tenantId}:*";
            $keys = Redis::keys($pattern);

            if (!empty($keys)) {
                Redis::del($keys);
            }

            $counterPattern = "tenant_job_counters:{$tenantId}";
            $counterKeys = Redis::keys($counterPattern);
            if (!empty($counterKeys)) {
                Redis::del($counterKeys);
            }

            $listPattern = "tenant_job_lists:{$tenantId}:*";
            $listKeys = Redis::keys($listPattern);
            if (!empty($listKeys)) {
                Redis::del($listKeys);
            }

            return response()->json(['message' => 'Tenant job data cleared successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to clear tenant job data'], 500);
        }
    }
}

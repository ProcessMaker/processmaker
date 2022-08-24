<?php

namespace ProcessMaker\Repositories;

class RedisJobRepository extends \Laravel\Horizon\Repositories\RedisJobRepository
{
    /**
     * Check if a given job (by instance or class) is already pending in the queue
     *
     * @param $job
     *
     * @return bool
     */
    public function isPending($job): bool
    {
        if (is_object($job)) {
            $job = class_basename($job);
        }

        return $this->getRecent()->filter(function ($pending_job) use ($job) {
            return $pending_job->name === $job
                && ($pending_job->status === 'delayed' ||
                    $pending_job->status === 'pending');
        })->isNotEmpty();
    }
}

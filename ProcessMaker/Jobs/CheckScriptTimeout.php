<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Exception\ScriptTimeoutException;

/**
 * The microservice handles script timeouts but this is an additional check
 * in case the microservice does not respond.
 */
class CheckScriptTimeout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $executionId;

    const ADDITIONAL_TIME = 5;

    public function __construct(string $executionId)
    {
        $this->executionId = $executionId;
    }

    public function handle()
    {
        if (Cache::has($this->executionId)) {
            throw new ScriptTimeoutException('Script execution timed out for execution ID: ' . $this->executionId);
        }
    }

    public static function start(string $executionId, int $timeout)
    {
        Cache::put($executionId, 'timeout: ' . $timeout, 86400); // hold in cache for a maximum of 24 hours
        self::dispatch($executionId)
            ->delay(now()->addSeconds($timeout + self::ADDITIONAL_TIME));
    }

    public static function stop(string $executionId)
    {
        Cache::forget($executionId);
    }
}

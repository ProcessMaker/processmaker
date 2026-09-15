<?php

namespace ProcessMaker\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Assets\ScreensInProcess;
use ProcessMaker\Models\Column;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use Throwable;

class ProcessScreenVariableService
{
    private const CACHE_FRESH_SECONDS = 300;

    private const CACHE_STALE_SECONDS = 3600;

    private const CACHE_LOCK_SECONDS = 120;

    /**
     * Resolve screen variables without traversing the full export dependency graph.
     */
    public function forProcesses(iterable $processes): Collection
    {
        $processes = collect($processes)
            ->filter(fn ($process) => $process instanceof Process)
            ->unique('id')
            ->sortBy('id')
            ->values();

        if ($processes->isEmpty()) {
            return collect();
        }

        $processSignature = $processes->map(function (Process $process) {
            return implode(':', [
                $process->id,
                (string) $process->getRawOriginal('updated_at'),
                sha1((string) $process->getRawOriginal('bpmn')),
            ]);
        })->implode('|');
        $cacheKey = 'process-screen-variables:v1:' . sha1($processSignature);
        $lastKnownKey = $cacheKey . ':last-known-good';
        $payload = Cache::flexible(
            $cacheKey,
            [self::CACHE_FRESH_SECONDS, self::CACHE_STALE_SECONDS],
            fn () => $this->refresh($processes, $cacheKey, $lastKnownKey)
        );

        return collect(is_array($payload) ? $payload : [])
            ->map(fn (array $column) => new Column($column));
    }

    private function refresh(Collection $processes, string $cacheKey, string $lastKnownKey): array
    {
        $lastKnown = Cache::get($lastKnownKey);
        $lock = Cache::lock($cacheKey . ':lock', self::CACHE_LOCK_SECONDS);

        if (!$lock->get()) {
            try {
                return $lock->block(
                    5,
                    fn () => Cache::get($lastKnownKey, is_array($lastKnown) ? $lastKnown : [])
                );
            } catch (LockTimeoutException) {
                return is_array($lastKnown) ? $lastKnown : [];
            }
        }

        $startedAt = microtime(true);
        $failed = false;

        try {
            $resolver = new ScreensInProcess();
            $screenIds = collect();

            foreach ($processes as $process) {
                try {
                    foreach ($resolver->referencesToExport($process) as [$class, $id]) {
                        if ($class === Screen::class) {
                            $screenIds->push($id);
                        }
                    }
                } catch (Throwable $exception) {
                    $failed = true;
                    Log::warning('Unable to resolve process screens for variable discovery', [
                        'process_id' => $process->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            $columns = collect();
            $screens = Screen::whereIn('id', $screenIds->unique())
                ->where('type', '!=', 'DISPLAY')
                ->get();

            foreach ($screens as $screen) {
                try {
                    $columns = $columns->merge($screen->fields->map(function ($column) {
                        $column->field = 'data.' . $column->field;

                        return $column;
                    }));
                } catch (Throwable $exception) {
                    $failed = true;
                    Log::warning('Unable to resolve screen fields for variable discovery', [
                        'screen_id' => $screen->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            $payload = $columns
                ->unique('field')
                ->values()
                ->map(fn ($column) => get_object_vars($column))
                ->all();

            if ($failed && is_array($lastKnown)) {
                return $lastKnown;
            }

            if (!$failed) {
                Cache::put($lastKnownKey, $payload, self::CACHE_STALE_SECONDS);
            }

            return $payload;
        } catch (Throwable $exception) {
            Log::warning('Screen variable discovery failed', [
                'process_count' => $processes->count(),
                'message' => $exception->getMessage(),
            ]);

            return is_array($lastKnown) ? $lastKnown : [];
        } finally {
            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
            Log::debug('Process screen variable discovery timing', [
                'process_count' => $processes->count(),
                'duration_ms' => $durationMs,
            ]);
            $lock->release();
        }
    }
}

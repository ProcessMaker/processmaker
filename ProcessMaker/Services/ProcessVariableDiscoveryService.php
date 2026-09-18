<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Assets\ScreensInProcess;
use ProcessMaker\Models\Column;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Package\VariableFinder\Models\ProcessVariable;
use Throwable;

class ProcessVariableDiscoveryService
{
    private const CACHE_FRESH_SECONDS = 300;

    private const CACHE_STALE_SECONDS = 3600;

    private const CACHE_LOCK_SECONDS = 120;

    private const LAST_KNOWN_SECONDS = 3600;

    private const UNSCOPED_SCREEN_PROCESS_LIMIT = 25;

    private const MYSQL_SORT_MEMORY_ERROR = 1038;

    /**
     * Resolve data.* columns for the given processes.
     *
     * @param  list<int>  $processIds
     */
    public function forProcessIds(array $processIds, bool $useVariableFinder = true): Collection
    {
        $processIds = array_values(array_unique(array_filter(array_map('intval', $processIds))));

        if ($processIds === []) {
            return $this->unscoped($useVariableFinder);
        }

        return $this->remember(
            $this->scopedCacheKey($processIds, $useVariableFinder),
            fn () => $this->resolveForProcessIds($processIds, $useVariableFinder)
        );
    }

    /**
     * Resolve data.* columns across the catalog when a Saved Search has no process scope.
     */
    public function unscoped(bool $useVariableFinder = true): Collection
    {
        return $this->remember(
            $this->unscopedCacheKey($useVariableFinder),
            fn () => $this->resolveUnscoped($useVariableFinder)
        );
    }

    /**
     * @param  list<int>  $processIds
     * @return list<array<string, mixed>>
     */
    private function resolveForProcessIds(array $processIds, bool $useVariableFinder): array
    {
        if ($useVariableFinder) {
            $fromFinder = $this->columnsFromVariableFinder($processIds);
            if ($fromFinder !== null) {
                return $fromFinder;
            }
        }

        return $this->columnsFromScreens(
            Process::whereIn('id', $processIds)->orderBy('id')->get()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveUnscoped(bool $useVariableFinder): array
    {
        if ($useVariableFinder) {
            $fromFinder = $this->columnsFromVariableFinder([]);
            if ($fromFinder !== null) {
                return $fromFinder;
            }
        }

        return $this->columnsFromScreens(
            Process::query()
                ->orderByDesc('updated_at')
                ->limit(self::UNSCOPED_SCREEN_PROCESS_LIMIT)
                ->get()
        );
    }

    /**
     * @param  list<int>  $processIds
     * @return list<array<string, mixed>>|null
     */
    private function columnsFromVariableFinder(array $processIds): ?array
    {
        if (!$this->variableFinderAvailable()) {
            return null;
        }

        try {
            $query = DB::table('asset_variables as av')
                ->join('var_finder_variables as vfv', 'av.id', '=', 'vfv.asset_variable_id')
                ->groupBy('vfv.field', 'vfv.label')
                ->orderBy('vfv.field')
                ->select([
                    'vfv.field',
                    'vfv.label',
                    DB::raw('MAX(vfv.data_type) as format'),
                ]);

            if ($processIds !== []) {
                $query->whereIn('av.process_id', $processIds);
            }

            return $query->get()
                ->map(fn ($row) => $this->columnArray(
                    $this->prefixedField((string) $row->field),
                    $row->label,
                    $row->format
                ))
                ->unique('field')
                ->values()
                ->all();
        } catch (QueryException $exception) {
            if (!$this->isMysqlSortMemoryError($exception)) {
                throw $exception;
            }

            Log::warning('Variable Finder exceeded MySQL sort memory; using screen variables', [
                'process_count' => count($processIds),
            ]);

            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function columnsFromScreens(Collection $processes): array
    {
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
                $columns = $columns->merge(
                    $screen->fields->map(fn ($column) => $this->columnArray(
                        $this->prefixedField((string) $column->field),
                        $column->label,
                        $column->format
                    ))
                );
            } catch (Throwable $exception) {
                Log::warning('Unable to resolve screen fields for variable discovery', [
                    'screen_id' => $screen->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $columns->unique('field')->values()->all();
    }

    /**
     * @param  callable(): list<array<string, mixed>>  $resolver
     */
    private function remember(string $cacheKey, callable $resolver): Collection
    {
        $lastKnownKey = $cacheKey . ':last-known-good';
        $payload = Cache::flexible(
            $cacheKey,
            [self::CACHE_FRESH_SECONDS, self::CACHE_STALE_SECONDS],
            fn () => $this->refresh($cacheKey, $lastKnownKey, $resolver)
        );

        return collect(is_array($payload) ? $payload : [])
            ->map(fn (array $column) => new Column($column));
    }

    /**
     * @param  callable(): list<array<string, mixed>>  $resolver
     * @return list<array<string, mixed>>
     */
    private function refresh(string $cacheKey, string $lastKnownKey, callable $resolver): array
    {
        $lastKnown = Cache::get($lastKnownKey);
        $lock = Cache::lock($cacheKey . ':lock', self::CACHE_LOCK_SECONDS);

        if (!$lock->get()) {
            // Never return an empty list here: Cache::flexible would persist it
            // as the fresh payload and hide variables for the full cache window.
            return $this->waitForLock($lock, $lastKnownKey, $lastKnown, $resolver);
        }

        try {
            return $this->resolveAndRemember($lastKnownKey, $lastKnown, $resolver);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  mixed  $lastKnown
     * @param  callable(): list<array<string, mixed>>  $resolver
     * @return list<array<string, mixed>>
     */
    private function waitForLock($lock, string $lastKnownKey, $lastKnown, callable $resolver): array
    {
        try {
            return $lock->block(
                5,
                fn () => Cache::get($lastKnownKey, is_array($lastKnown) ? $lastKnown : [])
            );
        } catch (LockTimeoutException) {
            if (is_array($lastKnown) && $lastKnown !== []) {
                return $lastKnown;
            }

            // No last-known-good payload: resolve directly so we do not cache an empty result.
            return $this->resolveAndRemember($lastKnownKey, $lastKnown, $resolver);
        }
    }

    /**
     * @param  mixed  $lastKnown
     * @param  callable(): list<array<string, mixed>>  $resolver
     * @return list<array<string, mixed>>
     */
    private function resolveAndRemember(string $lastKnownKey, $lastKnown, callable $resolver): array
    {
        try {
            $payload = $resolver();
            Cache::put($lastKnownKey, $payload, self::LAST_KNOWN_SECONDS);

            return $payload;
        } catch (Throwable $exception) {
            Log::warning('Process variable discovery failed', [
                'message' => $exception->getMessage(),
            ]);

            return is_array($lastKnown) ? $lastKnown : [];
        }
    }

    /**
     * @param  list<int>  $processIds
     */
    private function scopedCacheKey(array $processIds, bool $useVariableFinder): string
    {
        sort($processIds);
        $processStamp = Process::whereIn('id', $processIds)->pluck('updated_at', 'id')->toJson();
        $finderStamp = $useVariableFinder ? $this->variableFinderFingerprint() : 'screens';

        return 'process-variables:scoped:v1:' . hash('xxh128', $processStamp . '|' . $finderStamp);
    }

    private function unscopedCacheKey(bool $useVariableFinder): string
    {
        if ($useVariableFinder && $this->variableFinderAvailable()) {
            return 'process-variables:unscoped:v1:' . hash('xxh128', $this->variableFinderFingerprint());
        }

        $processStamp = Process::query()
            ->orderByDesc('updated_at')
            ->limit(self::UNSCOPED_SCREEN_PROCESS_LIMIT)
            ->pluck('updated_at', 'id')
            ->toJson();

        return 'process-variables:unscoped-screens:v1:' . hash('xxh128', $processStamp);
    }

    private function variableFinderFingerprint(): string
    {
        if (!$this->variableFinderAvailable()) {
            return '';
        }

        $fingerprint = DB::table('var_finder_variables')
            ->selectRaw('COUNT(*) as aggregate_count, MAX(updated_at) as max_updated')
            ->first();

        return json_encode($fingerprint) ?: '';
    }

    private function variableFinderAvailable(): bool
    {
        return class_exists(ProcessVariable::class)
            && Schema::hasTable('process_variables')
            && Schema::hasTable('var_finder_variables')
            && Schema::hasTable('asset_variables');
    }

    private function isMysqlSortMemoryError(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === self::MYSQL_SORT_MEMORY_ERROR;
    }

    private function prefixedField(string $field): string
    {
        return str_starts_with($field, 'data.') ? $field : 'data.' . $field;
    }

    /**
     * @return array<string, mixed>
     */
    private function columnArray(string $field, mixed $label, mixed $format): array
    {
        return [
            'label' => $label,
            'field' => $field,
            'sortable' => true,
            'default' => false,
            'format' => $format,
            'mask' => null,
        ];
    }
}

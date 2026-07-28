<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Services\ScriptMicroserviceService;

class TransitionExecutors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:transition-executors
                            {uuid : The script executor UUID, or "all"}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transition non-default script executor(s) to the Script Microservice';

    public function __construct(private ScriptMicroserviceService $scriptMicroserviceService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * Always talks to the microservice, even when SCRIPT_MICROSERVICE_ENABLED is false.
     */
    public function handle(): int
    {
        $executors = $this->resolveExecutors($this->argument('uuid'));

        if ($executors === null) {
            return 1;
        }

        if ($executors->isEmpty()) {
            $this->warn('No script executors found to transition.');

            return 0;
        }

        $isAll = $this->argument('uuid') === 'all';
        $remaining = $executors->count();
        $processed = 0;

        foreach ($executors as $executor) {
            $remaining--;
            if ($executor->language === 'php-nayra') {
                $this->info("skipping executor {$executor->uuid} ({$executor->language}) because it is a nayra executor.");
                continue;
            }

            $this->info("Transitioning executor {$executor->uuid} ({$executor->language}) to the microservice...");

            try {
                $response = $this->scriptMicroserviceService->updateCustomExecutor($executor);
                Log::debug('Response', ['response' => $response]);
                $status = strtolower((string) ($response['status'] ?? ''));

                if (in_array($status, ['error', 'failed', 'failure'], true) && !isset($response['executor_id'])) {
                    throw new \RuntimeException(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'Transition failed');
                }
            } catch (RequestException $e) {
                $this->error("Transition failed for executor {$executor->uuid}");
                $this->line($e->response?->body() ?: $e->getMessage());
                if ($isAll && $remaining > 0) {
                    $this->warn("Stopping: {$remaining} remaining executor(s) were not processed.");
                }

                return 1;
            } catch (\Throwable $e) {
                $this->error("Transition failed for executor {$executor->uuid}");
                $this->line($e->getMessage());
                if ($isAll && $remaining > 0) {
                    $this->warn("Stopping: {$remaining} remaining executor(s) were not processed.");
                }

                return 1;
            }

            $this->info("Executor {$executor->uuid} transitioned successfully.");
            $processed++;
        }

        if ($isAll) {
            $this->info("All script executors transitioned successfully. ({$processed} processed)");
        }

        return 0;
    }

    /**
     * Resolve executors that should be transitioned.
     *
     * Includes:
     * - type = custom
     * - type null (or unset) that are NOT the default/first executor for their language
     *
     * Excludes:
     * - default package executors (first row per language)
     *
     * @return Collection<int, ScriptExecutor>|null Null when the request is invalid.
     */
    private function resolveExecutors(string $uuid): ?Collection
    {
        if ($uuid === 'all') {
            return ScriptExecutor::query()
                ->orderBy('id')
                ->get()
                ->filter(fn (ScriptExecutor $executor) => $this->shouldTransition($executor))
                ->values();
        }

        if (!$this->isValidUuid($uuid)) {
            $this->error('Invalid uuid. Provide a script executor UUID or "all".');

            return null;
        }

        $executor = ScriptExecutor::where('uuid', $uuid)->first();

        if (!$executor) {
            $this->error("Script executor [{$uuid}] not found.");

            return null;
        }

        if (!$this->shouldTransition($executor)) {
            $this->error("Script executor [{$uuid}] is a default/system executor and cannot be transitioned.");

            return null;
        }

        return new Collection([$executor]);
    }

    /**
     * Whether this executor should be migrated to the microservice.
     *
     * Custom executors always qualify. Others qualify only when they are not
     * the default (first installed) executor for their language.
     */
    private function shouldTransition(ScriptExecutor $executor): bool
    {
        if ($executor->type === ScriptExecutorType::Custom) {
            return true;
        }

        $initial = ScriptExecutor::query()
            ->where('language', $executor->language)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        return !$initial || (int) $initial->id !== (int) $executor->id;
    }

    private function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }
}

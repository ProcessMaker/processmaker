<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Repositories\CaseRepository;

class CaseUpdate implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ExecutionInstanceInterface $instance, 
        protected TokenInterface $token,
        protected bool $shouldBatch = true
    ) {
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'case_update_' . $this->instance->case_number;
    }

    /**
     * Execute the job with batching optimization.
     */
    public function handle(CaseRepository $caseRepository): void
    {
        if (!$this->shouldUseBatch()) {
            $caseRepository->update($this->instance, $this->token);

            return;
        }

        $cacheKey = "case_update_batch_{$this->instance->case_number}";
        $cacheTtl = (int) config('queue-optimization.batching.case_updates.cache_ttl', 5);
        $batchTimeout = (int) config('queue-optimization.batching.case_updates.batch_timeout', 3);

        try {
            $batch = Cache::get($cacheKey, []);
            $batch[] = [
                'instance_id' => $this->instance->getKey(),
                'token_id' => $this->token->getKey(),
                'timestamp' => Carbon::now()->timestamp,
            ];

            Cache::put($cacheKey, $batch, $cacheTtl);

            if (count($batch) === 1) {
                dispatch(new ProcessCaseBatch($this->instance->case_number))
                    ->delay($batchTimeout)
                    ->onQueue('bpmn-batch');
            }
        } catch (\Exception $e) {
            Log::error('CaseUpdate batching failed, falling back to direct update', [
                'case_number' => $this->instance->case_number,
                'error' => $e->getMessage(),
            ]);

            $caseRepository->update($this->instance, $this->token);
        }
    }

    /**
     * Whether this job should accumulate tokens and process them in a batch.
     */
    private function shouldUseBatch(): bool
    {
        return $this->shouldBatch && (bool) config('queue-optimization.batching.case_updates.enabled', false);
    }

    /**
     * Create an immediate (non-batched) case update job.
     */
    public static function immediate(ExecutionInstanceInterface $instance, TokenInterface $token): self
    {
        return new self($instance, $token, false);
    }
}

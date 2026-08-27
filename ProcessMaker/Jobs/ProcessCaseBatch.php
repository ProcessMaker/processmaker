<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Repositories\CaseRepository;

class ProcessCaseBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $caseNumber)
    {
    }

    public function getCaseNumber(): int
    {
        return $this->caseNumber;
    }

    /**
     * Execute the job - process all batched tokens for a case.
     */
    public function handle(CaseRepository $caseRepository): void
    {
        $cacheKey = "case_update_batch_{$this->caseNumber}";
        
        try {
            // Get and clear the batch
            $batch = Cache::pull($cacheKey, []);
            
            if (empty($batch)) {
                Log::info("No batch found for case {$this->caseNumber}");
                return;
            }

            Log::info("Processing case batch", [
                'case_number' => $this->caseNumber,
                'token_count' => count($batch),
            ]);

            // Get the latest token and instance for this case
            $latestToken = $this->getLatestTokenFromBatch($batch);
            if (!$latestToken) {
                Log::warning("Could not find valid token for case batch {$this->caseNumber}");
                return;
            }

            $instance = $latestToken->processRequest;
            if (!$instance) {
                Log::warning("Could not find process request for case batch {$this->caseNumber}");
                return;
            }

            // Process using the optimized batch repository method
            $caseRepository->updateBatch($instance, $batch);
            
        } catch (\Exception $e) {
            Log::error('ProcessCaseBatch failed', [
                'case_number' => $this->caseNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-queue individual updates as fallback
            $this->fallbackToIndividualUpdates($batch, $caseRepository);
        }
    }

    /**
     * Get the most recent token from the batch.
     */
    private function getLatestTokenFromBatch(array $batch): ?ProcessRequestToken
    {
        if (empty($batch)) {
            return null;
        }

        // Sort by timestamp to get the latest
        usort($batch, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $latestTokenData = $batch[0];
        return ProcessRequestToken::find($latestTokenData['token_id']);
    }

    /**
     * Fallback to individual updates if batch processing fails.
     */
    private function fallbackToIndividualUpdates(array $batch, CaseRepository $caseRepository): void
    {
        foreach ($batch as $tokenData) {
            try {
                $token = ProcessRequestToken::find($tokenData['token_id']);
                $instance = ProcessRequest::find($tokenData['instance_id']);
                
                if ($token && $instance) {
                    $caseRepository->update($instance, $token);
                }
            } catch (\Exception $e) {
                Log::error('Individual fallback update failed', [
                    'token_id' => $tokenData['token_id'],
                    'instance_id' => $tokenData['instance_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
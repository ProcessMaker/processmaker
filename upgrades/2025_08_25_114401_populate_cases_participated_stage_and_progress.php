<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateCasesParticipatedStageAndProgress extends Upgrade
{
    /**
     * Batch size for processing large tables
     */
    private const BATCH_SIZE = 10000;

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        echo PHP_EOL . '    Populating cases_participated with stage and progress data...' . PHP_EOL;
        $startTime = microtime(true);

        // Update last_stage_name and progress based on case_status using optimized batch processing
        $this->updateStageAndProgressDataOptimized();
        $this->logTimeElapsed('Updated stage and progress data', $startTime);

        echo PHP_EOL;
    }

    /**
     * Log the time elapsed since the start of the process.
     *
     * @param string $message Message to log
     * @param float $startTime Time when the processing started (in microseconds)
     * @return void
     */
    private function logTimeElapsed(string $message, float $startTime): void
    {
        $currentTime = microtime(true);
        $timeElapsed = $currentTime - $startTime;

        // Format the elapsed time to 4 decimal places for higher precision
        echo "    {$message} - Time elapsed: " . number_format($timeElapsed, 4) . ' seconds' . PHP_EOL;
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        DB::table('cases_started')->update([
            'last_stage_name' => null,
            'progress' => 0,
        ]);
        DB::table('cases_participated')->update([
            'last_stage_name' => null,
            'progress' => 0,
        ]);
    }

    /**
     * Update the last_stage_name and progress fields using optimized batch processing
     *
     * @return void
     */
    private function updateStageAndProgressDataOptimized()
    {
        // Process COMPLETED cases in batches
        $this->processBatchByStatus('cases_participated', 'COMPLETED', 'COMPLETED', 100, 'COMPLETED cases with progress 100%');

        // Process IN_PROGRESS cases in batches
        $this->processBatchByStatus('cases_participated', 'IN_PROGRESS', 'IN_PROGRESS', 50, 'IN_PROGRESS cases with progress 50%');

        // Process COMPLETED cases in batches
        $this->processBatchByStatus('cases_started', 'COMPLETED', 'COMPLETED', 100, 'COMPLETED cases with progress 100%');

        // Process IN_PROGRESS cases in batches
        $this->processBatchByStatus('cases_started', 'IN_PROGRESS', 'IN_PROGRESS', 50, 'IN_PROGRESS cases with progress 50%');
    }

    /**
     * Process cases by status in batches for better performance
     *
     * @param string $status
     * @param string $stageName
     * @param int $progress
     * @param string $description
     * @return void
     */
    private function processBatchByStatus(string $tableName, string $status, string $stageName, int $progress, string $description)
    {
        $offset = 0;
        $totalProcessed = 0;

        do {
            // Get batch of IDs to process
            $batchIds = DB::table($tableName)
                ->where('case_status', $status)
                ->where(function ($query) {
                    $query->whereNull('last_stage_name')
                        ->orWhereNull('progress');
                })
                ->select('id')
                ->offset($offset)
                ->limit(self::BATCH_SIZE)
                ->pluck('id')
                ->toArray();

            if (empty($batchIds)) {
                break;
            }

            // Update batch using raw SQL for better performance
            $updated = DB::table($tableName)
                ->whereIn('id', $batchIds)
                ->update([
                    'last_stage_name' => $stageName,
                    'progress' => $progress,
                ]);

            $totalProcessed += $updated;
            $offset += self::BATCH_SIZE;

            // Progress indicator for large tables
            if ($totalProcessed % 50000 === 0) {
                echo "        Processed {$tableName} table {$totalProcessed} {$status} cases..." . PHP_EOL;
            }
        } while (count($batchIds) === self::BATCH_SIZE);

        if ($totalProcessed > 0) {
            echo "        Updated {$tableName} table {$totalProcessed} {$description}" . PHP_EOL;
        }
    }
}

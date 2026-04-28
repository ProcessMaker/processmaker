<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateCasesStartedAndParticipatedWithStagesAndProgress extends Upgrade
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
            'last_stage_id' => null,
            'last_stage_name' => null,
            'progress' => 0,
        ]);
        DB::table('cases_participated')->update([
            'last_stage_id' => null,
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
        // Define status mappings with their corresponding stage names and progress values
        $statusMappings = [
            'COMPLETED' => ['stage' => 'COMPLETED', 'progress' => 100],
            'IN_PROGRESS' => ['stage' => 'IN_PROGRESS', 'progress' => 50],
            'ACTIVE' => ['stage' => 'IN_PROGRESS', 'progress' => 50],
            'DRAFT' => ['stage' => 'DRAFT', 'progress' => 0],
            'ERROR' => ['stage' => 'ERROR', 'progress' => 0],
            'CANCELED' => ['stage' => 'CANCELED', 'progress' => 0],
            'PAUSED' => ['stage' => 'PAUSED', 'progress' => 0],
        ];

        // Process each status for cases_participated table
        foreach ($statusMappings as $status => $mapping) {
            $this->processBatchByStatus(
                'cases_participated',
                $status,
                $mapping['stage'],
                $mapping['progress'],
                "{$status} cases with progress {$mapping['progress']}%"
            );
        }

        // Process each status for cases_started table
        foreach ($statusMappings as $status => $mapping) {
            $this->processBatchByStatus(
                'cases_started',
                $status,
                $mapping['stage'],
                $mapping['progress'],
                "{$status} cases with progress {$mapping['progress']}%"
            );
        }
    }

    /**
     * Process cases by status in batches for better performance
     *
     * @param string $tableName
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
            // Get batch of IDs to process - now we update ALL records with this status
            // regardless of their current last_stage_name or progress values
            $batchIds = DB::table($tableName)
                ->where('case_status', $status)
                ->whereNull('last_stage_name')
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

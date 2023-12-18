<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateProcessRequestsCaseNumber extends Upgrade
{
    const CHUNK_SIZE = 5000;

    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * Throw a \RuntimeException if the conditions are *NOT* correct for this
     * upgrade migration to run. If this is not a required upgrade, then it
     * will be skipped. Otherwise the exception thrown will be caught, noted,
     * and will prevent the remaining migrations from continuing to run.
     *
     * Returning void or null denotes the checks were successful.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function preflightChecks()
    {
        //
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $caseNumber = 1;
        $chunkSize = self::CHUNK_SIZE;
        $startTime = microtime(true);

        $count = $this->getParentNonSystemRequests()->count();

        // Truncate the table case_numbers
        DB::table('case_numbers')->truncate();

        // Get all the process requests that don't have a parent request and are not system processes
        $this->getParentNonSystemRequests()->orderBy('process_requests.id')
        ->chunk($chunkSize, function ($records) use (&$caseNumber, $startTime, $count) {
            $inserts = [];
            foreach ($records as $record) {
                // UPDATE process_requests SET case_number = {$caseNumber} WHERE id = {$record->id};
                DB::table('process_requests')
                    ->where('id', $record->id)
                    ->update(['case_number' => $caseNumber]);
                $inserts[] = [
                    'id' => $caseNumber,
                    'process_request_id' => $record->id,
                ];
                $caseNumber++;
            }
            // INSERT INTO case_numbers in chunks
            DB::table('case_numbers')->insert($inserts);

            // Display the processing rate
            $this->displayRate($caseNumber, $startTime, $count);
        });

        // Update the auto increment value of table case_numbers to the next case number
        // ALTER TABLE case_numbers AUTO_INCREMENT = {$caseNumber};
        DB::statement("ALTER TABLE case_numbers AUTO_INCREMENT = {$caseNumber};");

        echo "\n";
    }

    private function getParentNonSystemRequests()
    {
        return DB::table('process_requests')
            ->select('process_requests.id')
            ->leftJoin('category_assignments', function ($join) {
                $join->on('process_requests.process_id', '=', 'category_assignments.assignable_id')
                    ->where('category_assignments.assignable_type', '=', 'ProcessMaker\Models\Process');
            })
            ->leftJoin('process_categories', 'category_assignments.category_id', '=', 'process_categories.id')
            ->whereNull('parent_request_id')
            ->where('process_categories.is_system', 0);
    }

    protected function displayRate($processed, $startTime, $count)
    {
        $currentTime = microtime(true);
        $timeElapsed = $currentTime - $startTime;
        $rate = $timeElapsed > 0 ? $processed / $timeElapsed : 0;

        // Clear current line
        echo "\r";

        // Write new rate
        echo "    #{$processed}/{$count} Processing rate: " . number_format($rate, 2) . ' requests/second';
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

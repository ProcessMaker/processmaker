<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateProcessRequestsCaseNumber extends Upgrade
{
    const CHUNK_SIZE = 5000;

    const REFRESH_TIME = 1;

    private $lastPrint = 0;

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
        echo '    Counting process requests...';
        $count = $this->getParentNonSystemRequests()->count();
        echo ' ', $count, PHP_EOL;

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
                    ->update([
                        'case_number' => $caseNumber,
                        'case_title' => 'Case #' . $caseNumber,
                        'case_title_formatted' => 'Case #<b>' . $caseNumber . '</b>',
                    ]);
                $inserts[] = [
                    'id' => $caseNumber,
                    'process_request_id' => $record->id,
                ];
                $caseNumber++;

                // Display the processing rate
                $this->displayRate($caseNumber, $startTime, $count, false);
            }
            // INSERT INTO case_numbers in chunks
            DB::table('case_numbers')->insert($inserts);

            // Display the processing rate
            $this->displayRate($caseNumber, $startTime, $count, true);
        });

        // Update the auto increment value of table case_numbers to the next case number
        // ALTER TABLE case_numbers AUTO_INCREMENT = {$caseNumber};
        DB::statement("ALTER TABLE case_numbers AUTO_INCREMENT = {$caseNumber};");

        echo PHP_EOL;

        // Copy case_number case_title and case_title_formatted from parent request to child requests
        echo '    Populating case_number, case_title and case_title_formatted from parent request to child requests...';
        DB::update('
            UPDATE process_requests
            JOIN process_requests AS parent_requests ON parent_requests.id = process_requests.parent_request_id
            SET process_requests.case_number = parent_requests.case_number,
                process_requests.case_title = parent_requests.case_title,
                process_requests.case_title_formatted = parent_requests.case_title_formatted
            WHERE
                process_requests.parent_request_id IS NOT NULL
                and not exists (
                    select 1 from category_assignments
                        left join process_categories on category_assignments.category_id = process_categories.id
                    where process_requests.process_id = category_assignments.assignable_id
                        and category_assignments.assignable_type = \'ProcessMaker\\\\Models\\\\Process\'
                        and process_categories.is_system = 1
                )
        ');

        echo PHP_EOL;
    }

    private function getParentNonSystemRequests()
    {
        return DB::table('process_requests')
            ->select('process_requests.id')
            ->whereNull('parent_request_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('category_assignments')
                    ->leftJoin('process_categories', 'category_assignments.category_id', '=', 'process_categories.id')
                    ->whereColumn('process_requests.process_id', 'category_assignments.assignable_id')
                    ->where('category_assignments.assignable_type', 'ProcessMaker\\Models\\Process')
                    ->where('process_categories.is_system', 1);
            });
    }

    protected function displayRate($processed, $startTime, $count, $forceShow)
    {
        $currentTime = microtime(true);
        if (!$forceShow && ($this->lastPrint + self::REFRESH_TIME > $currentTime)) {
            return;
        }
        $this->lastPrint = $currentTime;
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
        // Truncate the table case_numbers
        DB::table('case_numbers')->truncate();
        // Update the auto increment value of table case_numbers to 1
        DB::statement('ALTER TABLE case_numbers AUTO_INCREMENT = 1;');
        // Set the case_number of all the process requests to null
        DB::table('process_requests')->update([
            'case_number' => null,
            'case_title' => null,
            'case_title_formatted' => null,
        ]);
    }
}

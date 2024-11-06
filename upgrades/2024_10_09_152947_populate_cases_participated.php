<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateCasesParticipated extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $total_participants = $this->validateDataConsistency();

        DB::table('cases_participated')->delete();
        echo PHP_EOL . '    Populating case_participated from cases_started' . PHP_EOL;

        $startTime = microtime(true); // Start the timer

        $this->createTemporaryParticipantsTable();
        $this->logTimeElapsed('Created temporary table with participants', $startTime);

        $this->insertIntoCasesParticipated();
        $this->logTimeElapsed('Inserted data into cases_participated', $startTime);

        $count = DB::table('cases_participated')->count();

        echo PHP_EOL . "Cases Started participants. Total cases started participants: {$total_participants}" . PHP_EOL;
        echo PHP_EOL . "Cases Participated have been populated successfully. Total cases participated: {$count}" . PHP_EOL;

        echo PHP_EOL;
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        DB::table('cases_participated')->delete();
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
     * Creates a temporary table from process_request_tokens,
     * obtaining unique user_id by case_number
     *
     * @return void
     *
     * @throws Exception If there is an error creating the temporary table
     */
    private function createTemporaryParticipantsTable()
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS participants_temp2');

        // Build the query for `participants_temp2`
        $query = DB::table('cases_started as cs')
            ->select([
                DB::raw('participants.item as user_id'),
                'cs.case_number',
                'cs.case_title',
                'cs.case_title_formatted',
                'cs.case_status',
                'cs.processes',
                'cs.requests',
                'cs.request_tokens',
                'cs.tasks',
                'cs.participants',
                'cs.initiated_at',
                'cs.completed_at',
                'cs.created_at',
                'cs.updated_at',
                'cs.keywords',
            ])
            ->join(
                DB::raw('JSON_TABLE(cs.participants, \'$[*]\' COLUMNS(item INT PATH \'$\')) AS participants'),
                function ($join) {
                    // The join condition is essentially empty, just joins for each item in participants
                }
            );

        // Execute the query and create the temporary table
        DB::statement('CREATE TEMPORARY TABLE participants_temp2 AS ' . $query->toSql(), $query->getBindings());
    }

    private function insertIntoCasesParticipated()
    {
        $insertQuery = DB::table('participants_temp2 as part')
            ->select([
                'part.user_id',
                'part.case_number',
                'part.case_title',
                'part.case_title_formatted',
                'part.case_status',
                'part.processes',
                'part.requests',
                'part.request_tokens',
                'part.tasks',
                'part.participants',
                'part.initiated_at',
                'part.completed_at',
                'part.created_at',
                'part.updated_at',
                'part.keywords',
            ]);

        // Perform the insert and return the number of affected rows
        return DB::table('cases_participated')->insertUsing([
            'user_id', 'case_number', 'case_title', 'case_title_formatted', 'case_status',
            'processes', 'requests', 'request_tokens', 'tasks', 'participants',
            'initiated_at', 'completed_at', 'created_at', 'updated_at', 'keywords',
        ], $insertQuery);
    }

    /**
     * Check if exist inconsistency in "process_request" table
     */
    private function validateDataConsistency()
    {
        $results = DB::table('cases_started')
            ->select(DB::raw('sum(json_length(participants)) total_participants'))
            ->first();

        if (is_null($results)) {
            throw new Exception('Inconsistency detected, multiple records with null parent for the same request.');
        }

        return $results->total_participants;
    }
}

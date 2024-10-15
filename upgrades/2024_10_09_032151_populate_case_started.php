<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateCaseStarted extends Upgrade
{
    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are correct to run this upgrade.
     *
     * @return void
     *
     * @throws RuntimeException
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
        DB::table('cases_started')->truncate();
        echo PHP_EOL . '    Populating case_started from process_requests' . PHP_EOL;

        $startTime = microtime(true); // Start the timer

        try {
            $this->createTemporaryTableWithNonSystemRequests();
            $this->logTimeElapsed('Created temporary table with non-system requests', $startTime);

            $this->createTemporaryTableWithRequestTokens();
            $this->logTimeElapsed('Created temporary table with request tokens', $startTime);

            $this->insertIntoCasesStarted();
            $this->logTimeElapsed('Inserted data into cases_started', $startTime);

            echo PHP_EOL . 'Cases started have been populated successfully.' . PHP_EOL;
        } catch (Exception $e) {
            echo 'Failed to populate cases_started: ' . $e->getMessage() . PHP_EOL;
        }

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
     * Creates a temporary table from the process_requests table,
     * excluding system categories and previously populated requests.
     *
     * @return void
     */
    private function createTemporaryTableWithNonSystemRequests()
    {
        $query = DB::table('process_requests')
            ->join('processes', 'process_requests.process_id', '=', 'processes.id')
            ->join('process_categories', 'processes.process_category_id', '=', 'process_categories.id')
            ->whereNull('process_requests.parent_request_id') // Filter out subrequests
            ->where('process_categories.is_system', false) // Filter out system categories
            ->whereNotIn('process_requests.case_number', function ($subquery) {
                // Filter out requests that have already been populated in the cases_started table
                $subquery->select('case_number')->from('cases_started');
            })
            ->select(
                'process_requests.id',
                'process_requests.case_number',
                'process_requests.user_id',
                'process_requests.case_title',
                'process_requests.case_title_formatted',
                'process_requests.initiated_at',
                'process_requests.created_at',
                'process_requests.completed_at',
                'process_requests.updated_at',
                'process_requests.name',
                'process_requests.parent_request_id',
                'processes.id as process_id',
                'processes.name as process_name',
                DB::raw('
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            "id", processes.id,
                            "name", processes.name
                        )
                    ) as processes
                '), // Collect processes in an array of objects
                DB::raw('
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            "id", process_requests.id,
                            "name", process_requests.name,
                            "parent_request_id", process_requests.parent_request_id
                        )
                    ) as requests
                '), // Collect requests in an array of objects
                DB::raw("IF(process_requests.status = 'ACTIVE', 'IN_PROGRESS', process_requests.status) as status")
            )
            ->groupBy(
                'process_requests.id',
                'process_requests.case_number',
                'process_requests.user_id',
                'process_requests.case_title',
                'process_requests.case_title_formatted',
                'process_requests.initiated_at',
                'process_requests.created_at',
                'process_requests.completed_at',
                'process_requests.updated_at',
                DB::raw("IF(process_requests.status = 'ACTIVE', 'IN_PROGRESS', process_requests.status)")
            ); // Group by all selected fields that are not aggregated

        DB::statement('CREATE TEMPORARY TABLE process_requests_temp AS ' . $query->toSql(), $query->getBindings());
    }

    private function createTemporaryTableWithRequestTokens()
    {
        // Step 1: Create unique participants using Laravel's query builder
        $uniqueParticipantsQuery = DB::table('process_request_tokens as pr')
            ->select('pr.user_id', 'pr.element_id', 'pr.process_id', 'temp.case_number')
            ->join('process_requests_temp as temp', 'pr.process_request_id', '=', 'temp.id')
            ->where('pr.element_type', 'task')
            ->distinct(); // Use distinct to avoid duplicates

        DB::statement('CREATE TEMPORARY TABLE unique_participants AS ' . $uniqueParticipantsQuery->toSql(), $uniqueParticipantsQuery->getBindings());

        // Creating the query
        $query = DB::table('process_request_tokens as pr')
            ->join('process_requests_temp as temp', 'pr.process_request_id', '=', 'temp.id')
            ->select(
                'temp.case_number',
                DB::raw('
                    (SELECT JSON_ARRAYAGG(user_id) 
                        FROM (
                            SELECT DISTINCT user_id 
                            FROM unique_participants 
                            WHERE case_number = unique_participants.case_number
                        ) AS distinct_users) AS participants
                    '), // Aggregate unique user_ids
                DB::raw('JSON_ARRAYAGG(pr.id) as request_tokens'),
                DB::raw('JSON_ARRAYAGG(JSON_OBJECT(
                    "id", pr.id,
                    "name", pr.element_name,
                    "element_id", pr.element_id,
                    "process_id", pr.process_id
                )) as tasks')
            )
            ->whereIn('pr.element_type', ['task'])
            ->groupBy('temp.case_number');

        DB::statement('CREATE TEMPORARY TABLE process_request_tokens_tmp AS ' . $query->toSql(), $query->getBindings());
    }

    private function insertIntoCasesStarted()
    {
        // Create the select query
        $selectQuery = DB::table('process_requests_temp as prt1')
            ->join('process_request_tokens_tmp as prt2', 'prt1.case_number', '=', 'prt2.case_number')
            ->select(
                'prt1.case_number',
                'prt1.status as case_status',
                'prt1.case_title',
                'prt1.case_title_formatted',
                'prt1.completed_at',
                'prt1.created_at',
                'prt1.initiated_at',
                DB::raw('case_title as keywords'),
                'prt2.participants',
                'prt1.processes',
                'prt2.request_tokens',
                'prt1.requests',
                'prt2.tasks',
                'prt1.updated_at',
                'prt1.user_id'
            );

        // Insert the data into cases_started
        DB::table('cases_started')->insertUsing([
            'case_number',
            'case_status',
            'case_title',
            'case_title_formatted',
            'completed_at',
            'created_at',
            'initiated_at',
            'keywords',
            'participants',
            'processes',
            'request_tokens',
            'requests',
            'tasks',
            'updated_at',
            'user_id',
        ], $selectQuery);
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        // Truncate the table cases_started
        DB::table('cases_started')->truncate();
    }
}

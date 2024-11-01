<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Repositories\CaseUtils;
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
        $this->validateDataConsistency();

        DB::table('cases_participated')->truncate();
        echo PHP_EOL . '    Populating case_participated from process_requests' . PHP_EOL;

        $startTime = microtime(true); // Start the timer

        $this->createTemporaryTableWithNonSystemRequests();
        $this->logTimeElapsed('Created temporary table with non-system requests', $startTime);

        $this->createTemporaryParticipantsTable();
        $this->logTimeElapsed('Created temporary table with participants', $startTime);

        $this->insertIntoCasesParticipated();
        $this->logTimeElapsed('Inserted data into cases_participated', $startTime);

        $count = DB::table('cases_started')->count();

        echo PHP_EOL . "Cases Participated have been populated successfully. Total cases: {$count}" . PHP_EOL;

        echo PHP_EOL;
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        DB::table('cases_participated')->truncate();
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
     * Creates a temporary table with request tokens associated with
     * the process requests.
     *
     * @return void
     *
     * @throws Exception If there is an error creating the temporary table
     */
    private function createTemporaryTableWithNonSystemRequests()
    {
        // Creating the query
        $query = DB::table('process_requests')
        ->select([
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
            DB::raw("IF(process_requests.status = 'ACTIVE', 'IN_PROGRESS', process_requests.status) as status"),
            DB::raw("JSON_ARRAYAGG(
                JSON_OBJECT(
                    'id', processes.id,
                    'name', processes.name
                )
            ) as processes"),
            DB::raw("JSON_ARRAYAGG(
                JSON_OBJECT(
                    'id', process_requests.id,
                    'name', process_requests.name,
                    'parent_request_id', process_requests.parent_request_id
                )
            ) as requests"),
        ])
        ->join('processes', 'process_requests.process_id', '=', 'processes.id')
        ->join('process_categories', 'processes.process_category_id', '=', 'process_categories.id')
        ->whereNull('process_requests.parent_request_id')
        ->where('process_categories.is_system', '=', false)
        ->groupBy([
            'process_requests.id',
            'process_requests.case_number',
            'process_requests.user_id',
            'process_requests.case_title',
            'process_requests.case_title_formatted',
            'process_requests.initiated_at',
            'process_requests.created_at',
            'process_requests.completed_at',
            'process_requests.updated_at',
            DB::raw("IF(process_requests.status = 'ACTIVE', 'IN_PROGRESS', process_requests.status)"),
        ]);

        // Step 2: Execute the query and create a temporary table
        DB::statement('CREATE TEMPORARY TABLE process_requests_temp AS ' . $query->toSql(), $query->getBindings());
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
        // Build the query for `participants_temp`
        $query = DB::table('process_request_tokens as prt')
            ->select([
                'prt.user_id',
                'pr.case_number',
            ])
            ->join('process_requests_temp as pr', 'prt.process_request_id', '=', 'pr.id')
            ->whereNotNull('prt.user_id')
            ->where('prt.element_type', '=', 'task')
            ->groupBy(['pr.case_number', 'prt.user_id']);

        // Execute the query and create the temporary table
        DB::statement('CREATE TEMPORARY TABLE participants_temp AS ' . $query->toSql(), $query->getBindings());
    }

    private function insertIntoCasesParticipated()
    {
        $insertQuery = DB::table('cases_started as prt1')
            ->join('participants_temp as part', 'prt1.case_number', '=', 'part.case_number')
            ->select([
                'prt1.case_number',
                'prt1.case_status',
                'prt1.case_title',
                'prt1.case_title_formatted',
                'prt1.completed_at',
                'prt1.created_at',
                'prt1.initiated_at',
                'prt1.keywords',
                'prt1.participants',
                'prt1.processes',
                'prt1.request_tokens',
                'prt1.requests',
                'prt1.tasks',
                'prt1.updated_at',
                'part.user_id',
            ]);

        // Perform the insert and return the number of affected rows
        return DB::table('cases_participated')->insertUsing([
            'case_number', 'case_status', 'case_title', 'case_title_formatted',
            'completed_at', 'created_at', 'initiated_at', 'keywords',
            'participants', 'processes', 'request_tokens', 'requests',
            'tasks', 'updated_at', 'user_id',
        ], $insertQuery);
    }

    /**
     * Check if exist inconsitency in "process_request" table
     */
    private function validateDataConsistency()
    {
        $results = DB::table('process_requests')
            ->select('case_number', DB::raw('count(*) as total'))
            ->whereNull('parent_request_id')
            ->whereNotNull('case_number')
            ->groupBy('case_number')
            ->having('total', '>', 1)
            ->first();

        if (!is_null($results)) {
            throw new Exception('Inconsistency detected, multiple records with null parent for the same request.');
        }
    }
}

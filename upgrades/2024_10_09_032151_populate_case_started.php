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
        // Add preflight checks if necessary
    }

    /**
     * Run the upgrade migration to populate the cases_started table
     * from the process_requests table while filtering and processing
     * relevant data.
     *
     * @return void
     */
    public function up()
    {
        // @todo add a validation to check the data consistency
        $this->validateDataConsistency();

        DB::table('cases_started')->delete();
        echo PHP_EOL . '    Populating case_started from process_requests' . PHP_EOL;

        $startTime = microtime(true); // Start the timer

        $this->createTemporaryTableWithNonSystemRequests();
        $this->logTimeElapsed('Created temporary table with non-system requests', $startTime);

        $this->createTemporaryParticipantsTable();
        $this->logTimeElapsed('Created temporary table with participants', $startTime);

        $this->createTemporaryTableWithRequestTokens();
        $this->logTimeElapsed('Created temporary table with request tokens', $startTime);

        $this->insertIntoCasesStarted();
        $this->logTimeElapsed('Inserted data into cases_started', $startTime);

        $count = DB::table('cases_started')->count();

        echo PHP_EOL . "Cases started have been populated successfully. Total cases: {$count}" . PHP_EOL;

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
     *
     * @throws Exception If there is an error creating the temporary table
     */
    private function createTemporaryTableWithNonSystemRequests()
    {
        $query = DB::table('process_requests')
            ->join('processes', 'process_requests.process_id', '=', 'processes.id')
            ->join('process_categories', 'processes.process_category_id', '=', 'process_categories.id')
            ->where('process_categories.is_system', false) // Filter out system categories
            ->whereNotNull('process_requests.case_number')
            ->whereNull('process_requests.parent_request_id')
            ->whereNotIn('process_requests.case_number', function ($subquery) {
                // Filter out requests that have already been populated in the cases_started table
                $subquery->select('case_number')->from('cases_started');
            })
            ->select(
                DB::raw('min(process_requests.id) as id'),
                'process_requests.case_number',
                DB::raw('min(process_requests.user_id) as user_id'),
                DB::raw('max(process_requests.case_title) as case_title'),
                DB::raw('max(process_requests.case_title_formatted) as case_title_formatted'),
                DB::raw('min(process_requests.initiated_at) as initiated_at'),
                DB::raw('min(process_requests.created_at) as created_at'),
                DB::raw('max(process_requests.completed_at) as completed_at'),
                DB::raw('max(process_requests.updated_at) as updated_at'),
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
                DB::raw("max(IF(process_requests.status = 'ACTIVE', 'IN_PROGRESS', process_requests.status)) as status")
            )
            ->groupBy(
                'process_requests.case_number'
            ); // Group by all selected fields that are not aggregated

        DB::statement('CREATE TEMPORARY TABLE process_requests_temp AS ' . $query->toSql(), $query->getBindings());

        DB::statement(<<<SQL
        CREATE TEMPORARY TABLE process_requests_children_temp AS SELECT 
            pr.`case_number`, 
            JSON_ARRAYAGG(
                JSON_OBJECT(
                    "id", p.`id`,
                    "name", p.`name`
                )
            ) AS processes,
            JSON_ARRAYAGG(
                JSON_OBJECT(
                    "id", pr.`id`,
                    "name", pr.`name`,
                    "parent_request_id", pr.`parent_request_id`
                )
            ) AS requests
        FROM 
            `process_requests` pr
        INNER JOIN 
            `processes` p ON pr.`process_id` = p.`id`
        INNER JOIN 
            `process_categories` pc ON p.`process_category_id` = pc.`id`
        WHERE  
            pc.`is_system` = 0
            AND pr.`case_number` NOT IN (SELECT `case_number` FROM `cases_started`)
            AND case_number is not null
            AND pr.parent_request_id is not null
        GROUP BY 
            pr.`case_number`;
        SQL);
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
        DB::statement(<<<SQL
            CREATE temporary table participants_temp as
            SELECT JSON_ARRAYAGG(user_id) as participants,
            case_number
            from
                (   select
                        DISTINCT
                        pr.user_id,
                        temp.case_number
                    from
                        process_request_tokens pr inner join
                        process_requests temp on pr.process_request_id  = temp.id
                    where
                        pr.user_id is not null
                        and pr.element_type = 'task'
                ) X
            group by case_number;
        SQL);
    }

    /**
     * Creates a temporary table with request tokens associated with
     * the process requests.
     *
     * @return void
     *
     * @throws Exception If there is an error creating the temporary table
     */
    private function createTemporaryTableWithRequestTokens()
    {
        // Creating the query
        $query = DB::table('process_request_tokens as pr')
            ->join('process_requests as temp', 'pr.process_request_id', '=', 'temp.id')
            ->join('participants_temp as part', 'temp.case_number', '=', 'part.case_number')
            ->select(
                'temp.case_number',
                'part.participants',
                DB::raw('JSON_ARRAYAGG(pr.id) as request_tokens'),
                DB::raw('JSON_ARRAYAGG(JSON_OBJECT(
                    "id", pr.id,
                    "element_type", pr.element_type,
                    "status", pr.status,
                    "name", pr.element_name,
                    "element_id", pr.element_id,
                    "process_id", pr.process_id
                )) as tasks')
            )
            ->whereIn('pr.element_type', ['task'])
            ->groupBy('temp.case_number')
            ->groupBy('part.participants');

        DB::statement('CREATE TEMPORARY TABLE process_request_tokens_tmp AS ' . $query->toSql(), $query->getBindings());
    }

    /**
     * Inserts the processed data into the cases_started table.
     *
     * @return void
     *
     * @throws Exception If there is an error during the insertion process
     */
    private function insertIntoCasesStarted()
    {
        // Create the select query
        DB::statement(<<<'SQL'
            INSERT INTO cases_started (
                case_number, case_status, case_title, case_title_formatted,
                completed_at, created_at, initiated_at, keywords,
                participants, processes, request_tokens, requests,
                tasks, updated_at, user_id
            )
            SELECT 
                prt1.case_number,
                prt1.status as case_status,
                prt1.case_title,
                prt1.case_title_formatted,
                prt1.completed_at,
                prt1.created_at,
                prt1.initiated_at,
                CONCAT('cn_', prt1.case_number, ' ', prt1.case_title) as keywords,
                COALESCE(prt2.participants, JSON_ARRAY()) as participants,
                JSON_MERGE_PRESERVE(prt1.processes, COALESCE(prtc.processes, JSON_ARRAY())) as processes,
                COALESCE(prt2.request_tokens, JSON_ARRAY()) as request_tokens,
                JSON_MERGE_PRESERVE(prt1.requests, COALESCE(prtc.requests, JSON_ARRAY())) as requests,
                COALESCE(prt2.tasks, JSON_ARRAY()) as tasks,
                prt1.updated_at,
                prt1.user_id
            FROM process_requests_temp as prt1
            LEFT JOIN process_requests_children_temp as prtc
                ON prt1.case_number = prtc.case_number
            LEFT JOIN process_request_tokens_tmp as prt2 
                ON prt1.case_number = prt2.case_number
            SQL
        );
    }

    /**
     * Filters out tasks and removes the element_type key from the tasks data.
     *
     * @param string $jsonData JSON string containing tasks
     * @return array Filtered array of tasks without the element_type key
     */
    private function filterAndRemoveType($jsonData): array
    {
        // Decode the JSON string to an array
        $data = json_decode($jsonData, true);

        // Filter the data for tasks and remove the element_type key
        return array_values(array_map(
            fn ($item) => array_diff_key($item, ['element_type' => '']),
            array_filter($data, fn ($item) => $item['element_type'] === 'task')
        ));
    }

    /**
     * Cleans up the participants data by removing null values and duplicates.
     *
     * @param string $jsonParticipants JSON string containing participants
     * @return string JSON-encoded cleaned participants
     */
    private function cleanParticipants($jsonParticipants): string
    {
        // Decode the JSON string to an array
        $participants = json_decode($jsonParticipants, true);
        // Remove null values and duplicates
        $cleaned = array_unique(array_filter($participants));

        return json_encode(array_values($cleaned));
    }

    /**
     * Reverse the upgrade migration, typically used for rollback operations.
     *
     * @return void
     */
    public function down()
    {
        // You might want to drop the tables created or perform rollback actions
        DB::statement('DROP TEMPORARY TABLE IF EXISTS process_requests_temp');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS process_request_tokens_tmp');
        DB::table('cases_started')->truncate();
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
            $warning = 'Inconsistency detected, multiple records with null parent for the same request. '
                . 'Case number: ' . $results->case_number . ' Count of parent Requests: ' . $results->total;
            echo PHP_EOL . $warning . PHP_EOL;
            // Ask to continue
            if ($this->confirm($warning . ' Do you want to continue?')) {
                return;
            }
        }
    }

    function confirm($message)
    {
        // Show the confirmation message with a "yes/no" prompt
        echo $message . " (yes/no): ";
    
        // Get user input from the command line
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);
    
        // Check if the response is "yes"
        return strtolower($response) === 'yes';
    }
}

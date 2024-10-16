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
            // Handle errors during the population process
            echo 'Failed to populate cases_started: ' . $e->getMessage() . PHP_EOL;
            // You may want to log the error or perform other error handling here
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
     *
     * @throws Exception If there is an error creating the temporary table
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
            ->join('process_requests_temp as temp', 'pr.process_request_id', '=', 'temp.id')
            ->select(
                'temp.case_number',
                DB::raw('JSON_ARRAYAGG(pr.user_id) as participants'),
                DB::raw('JSON_ARRAYAGG(pr.id) as request_tokens'),
                DB::raw('JSON_ARRAYAGG(JSON_OBJECT(
                    "id", pr.id,
                    "element_type", pr.element_type,
                    "name", pr.element_name,
                    "element_id", pr.element_id,
                    "process_id", pr.process_id
                )) as tasks')
            )
            ->whereIn('pr.element_type', ['task', 'scriptTask', 'callActivity'])
            ->groupBy('temp.case_number');

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

        $results = $selectQuery->get();
        $dataToInsert = $results->map(function ($item) {
            return [
                'case_number' => $item->case_number,
                'case_status' => $item->case_status,
                'case_title' => $item->case_title,
                'case_title_formatted' => $item->case_title_formatted,
                'completed_at' => $item->completed_at,
                'created_at' => $item->created_at,
                'initiated_at' => $item->initiated_at,
                'keywords' => $item->keywords,
                'participants' => $this->cleanParticipants($item->participants),
                'processes' => $item->processes,
                'request_tokens' => $item->request_tokens,
                'requests' => $item->requests,
                'tasks' => json_encode($this->filterAndRemoveType($item->tasks)),
                'updated_at' => $item->updated_at,
                'user_id' => $item->user_id,
            ];
        })->toArray();

        // Insert data into the cases_started table
        DB::table('cases_started')->insert($dataToInsert);
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
}

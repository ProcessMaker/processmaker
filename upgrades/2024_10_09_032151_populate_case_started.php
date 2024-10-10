<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Repositories\CaseUtils;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateCaseStarted extends Upgrade
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
        $caseNumber = 1;
        $chunkSize = self::CHUNK_SIZE;
        $startTime = microtime(true);
        echo '    Counting process requests...';
        $count = $this->getNonSystemRequests()->count();
        echo ' ', $count, PHP_EOL;

        // Truncate the table cases_started
        DB::table('cases_started')->truncate();
        echo PHP_EOL;
        echo '    Populating case_started from process_requests';

        try {
            $this->getNonSystemRequests()
                ->orderBy('process_requests.id')
                ->chunk($chunkSize, function ($requests) use (&$caseNumber, $startTime, $count) {
                    $this->insertCasesStarted($requests);
                    $this->displayRate($caseNumber, $startTime, $count, false);
                });
            echo PHP_EOL;
            echo 'Cases started have been populated successfully.';
        } catch (Exception $e) {
            echo 'Failed to populate cases_started: ' . $e->getMessage();
        }

        echo PHP_EOL;
    }

    /**
     * Display the processing rate of requests based on the time elapsed.
     *
     * @param int     $processed The number of requests that have been processed so far.
     * @param float   $startTime The time when the processing started (in microseconds).
     * @param int     $count     The total number of requests to be processed.
     * @param bool    $forceShow Whether to force showing the rate, regardless of the refresh interval.
     * @return void
     */
    protected function displayRate(int $processed, float $startTime, int $count, bool $forceShow): void
    {
        // Get the current time in microseconds
        $currentTime = microtime(true);

        // Only update the display if the forceShow flag is set or enough time has passed since the last display
        if (!$forceShow && ($this->lastPrint + self::REFRESH_TIME > $currentTime)) {
            return; // Skip if refresh time hasn't elapsed
        }

        // Update the last print time
        $this->lastPrint = $currentTime;

        // Calculate the time elapsed since the start of processing
        $timeElapsed = $currentTime - $startTime;

        // Calculate the processing rate (requests per second), handle divide by zero case
        $rate = $timeElapsed > 0 ? $processed / $timeElapsed : 0;

        // Clear the current line in the console
        echo "\r";

        // Display the current processing progress and rate, formatted to 2 decimal places
        echo "    #{$processed}/{$count} Processing rate: " . number_format($rate, 2) . ' requests/second';
    }

    /**
     * Get non-system process requests.
     * Retrieves process requests where the parent is null
     * and filters out system processes.
     */
    private function getNonSystemRequests()
    {
        return DB::table('process_requests')
            ->join('processes', 'process_requests.process_id', '=', 'processes.id')
            ->join('process_categories', 'processes.process_category_id', '=', 'process_categories.id')
            ->whereNull('parent_request_id')
            ->where('process_categories.is_system', false)
            ->select(
                'process_requests.id',
                'process_requests.case_number',
                'process_requests.user_id',
                'process_requests.case_title',
                'process_requests.case_title_formatted',
                'process_requests.status',
                'process_requests.initiated_at',
                'process_requests.created_at',
                'process_requests.completed_at',
                'process_requests.updated_at',
                'process_requests.name',
                'process_requests.parent_request_id',
                'processes.id as process_id',
                'processes.name as process_name',
            );
    }

    /**
     * Retrieve tokens associated with multiple case numbers in batch.
     *
     * @param array $caseNumbers The case numbers used to filter process requests.
     * @return Illuminate\Support\Collection A collection of matching token records.
     */
    private function getTokensByCaseNumbers(array $caseNumbers)
    {
        return DB::table('process_request_tokens')
            ->select('id', 'element_id', 'element_name', 'user_id', 'process_id', 'element_type')
            ->whereIn('process_request_id', function ($query) use ($caseNumbers) {
                // Subquery to select process_request IDs related to the given case numbers
                $query->select('id')
                    ->from('process_requests')
                    ->whereIn('case_number', $caseNumbers);
            })
            ->get();
    }

    /**
     * Get JSON data for a process request.
     *
     * This method extracts the process ID and name from the given request object
     * and returns the data in a JSON-friendly format.
     *
     * @param object $request The request object containing the process information.
     * @return array The structured array containing the 'id' and 'name' of the process.
     */
    private function getProcessJsonData($request)
    {
        return $this->getJsonData($request, [
            'process_id' => 'id',
            'process_name' => 'name',
        ]);
    }

    /**
     * Get JSON data for a request.
     *
     * This method extracts the ID, name, and parent request ID from the given request object
     * and returns the data in a JSON-friendly format.
     *
     * @param object $request The request object containing the request information.
     * @return array The structured array containing the 'id', 'name', and 'parent_request_id' of the request.
     */
    private function getRequestJsonData($request)
    {
        return $this->getJsonData($request, [
            'id' => 'id',
            'name' => 'name',
            'parent_request_id' => 'parent_request_id',
        ]);
    }

    /**
     * General method to extract specified attributes from a request and format them into a JSON array.
     *
     * This method is used internally by both getProcessJsonData and getRequestJsonData to avoid redundancy.
     * It takes a request object and a mapping array that defines the attribute-to-key relationship.
     *
     * @param object $request The request object containing data.
     * @param array $mapping An associative array where keys are object properties and values are the corresponding JSON keys.
     * @return array The structured JSON array based on the provided mapping.
     */
    private function getJsonData($request, array $mapping)
    {
        $jsonData = [];

        if (!empty((array) $request)) {
            $formattedData = [];
            foreach ($mapping as $property => $key) {
                $formattedData[$key] = $request->{$property} ?? null; // Handle undefined properties
            }
            $jsonData[] = $formattedData;
        }

        return $jsonData;
    }

    /**
     * Get token IDs as JSON formatted array from matching tokens, filtering by allowed element types.
     *
     * @param Illuminate\Support\Collection $matchingRequestTokens
     * @return array
     */
    private function getTokenJsonData($matchingRequestTokens)
    {
        return $matchingRequestTokens->isNotEmpty()
            ? $matchingRequestTokens->filter(function ($token) {
                return in_array($token->element_type, CaseUtils::ALLOWED_REQUEST_TOKENS);
            })->pluck('id')->toArray()
            : [];
    }

    /**
     * Get unique user IDs as JSON formatted array from matching request tokens, filtering out null user IDs.
     *
     * @param Illuminate\Support\Collection $matchingRequestTokens
     * @return array
     */
    private function getParticipantJsonData($matchingRequestTokens)
    {
        return $matchingRequestTokens->isNotEmpty()
            ? $matchingRequestTokens->filter(function ($token) {
                return !is_null($token->user_id); // Filter out null user_ids
            })->pluck('user_id')->unique()->values()->toArray() // Get unique user IDs
            : [];
    }

    /**
     * Get JSON data for tasks from the provided matching request tokens.
     *
     * @param Illuminate\Support\Collection $matchingRequestTokens
     * @return array An array of objects containing 'id', 'element_id', 'name', and 'process_id'.
     */
    private function getTaskJsonData($matchingRequestTokens)
    {
        return $matchingRequestTokens->isNotEmpty()
            ? $matchingRequestTokens->filter(function ($token) {
                return in_array($token->element_type, CaseUtils::ALLOWED_ELEMENT_TYPES);
            })
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'element_id' => $token->element_id,
                    'name' => $token->element_name,
                    'process_id' => $token->process_id,
                ];
            })
            ->values() // Reset the keys to return an array of objects
            ->toArray()
            : [];
    }

    /**
     * Create the insert array for each request.
     *
     * @param object $request
     * @param array $data
     * @return array
     */
    private function createInsertData($request, array $data)
    {
        return [
            'case_number' => $request->case_number,
            'user_id' => $request->user_id,
            'case_title' => $request->case_title,
            'case_title_formatted' => $request->case_title_formatted,
            'case_status' => $request->status === 'ACTIVE' ? 'IN_PROGRESS' : $request->status,
            'processes' => json_encode($data['processes']),
            'requests' => json_encode($data['requests']),
            'request_tokens' => json_encode($data['tokens']),
            'tasks' => json_encode($data['tasks']),
            'participants' => json_encode($data['participants']),
            'initiated_at' => $request->initiated_at,
            'completed_at' => $request->completed_at,
            'keywords' => $request->case_title,
            'created_at' => $request->created_at,
            'updated_at' => $request->updated_at,
        ];
    }

    /**
     * Insert data into the cases_started table in chunks.
     *
     * @param Illuminate\Support\Collection $requests
     * @return void
     */
    private function insertCasesStarted($requests)
    {
        $inserts = [];

        $caseNumbers = $requests->pluck('case_number')->toArray(); // Collect case numbers for batch processing
        $matchingRequestTokens = $this->getTokensByCaseNumbers($caseNumbers); // Retrieve tokens in batch

        foreach ($requests as $request) {
            // Prepare JSON data
            $processJsonData = $this->getProcessJsonData($request);
            $requestJsonData = $this->getRequestJsonData($request);
            $tokenJsonData = $this->getTokenJsonData($matchingRequestTokens);
            $taskJsonData = $this->getTaskJsonData($matchingRequestTokens);
            $participantJsonData = $this->getParticipantJsonData($matchingRequestTokens);

            // Create the insert array
            $inserts[] = $this->createInsertData($request, [
                'processes' => $processJsonData,
                'requests' => $requestJsonData,
                'tokens' => $tokenJsonData,
                'tasks' => $taskJsonData,
                'participants' => $participantJsonData,
            ]);
        }

        // Insert data into the database in chunks
        DB::transaction(function () use ($inserts) {
            foreach (array_chunk($inserts, self::CHUNK_SIZE) as $insertChunk) {
                DB::table('cases_started')->insert($insertChunk);
            }
        });
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        // Truncate the table case_numbers
        DB::table('cases_started')->truncate();
    }
}

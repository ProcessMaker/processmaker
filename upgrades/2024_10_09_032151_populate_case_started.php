<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            ->whereNull('parent_request_id')
            ->join('processes', 'process_requests.process_id', '=', 'processes.id')
            ->join('process_categories', 'processes.process_category_id', '=', 'process_categories.id')
            ->where('process_categories.is_system', false)
            ->select(
                'process_requests.id',
                'process_requests.case_number',
                'process_requests.user_id',
                'process_requests.case_title',
                'process_requests.case_title_formatted',
                'process_requests.status',
                'process_requests.initiated_at'
            );
    }

    /**
     * Get matching requests data for a given case number.
     *
     * @param string $caseNumber
     * @return Illuminate\Support\Collection
     */
    private function getMatchingRequestDetails(string $caseNumber)
    {
        return DB::table('process_requests')
            ->where('case_number', $caseNumber)
            ->select('id', 'name', 'parent_request_id')  // Select specific fields
            ->get();
    }

    /**
     * Retrieve tokens associated with a specific case number.
     *
     * @param string $caseNumber The case number used to filter process requests.
     * @return Illuminate\Support\Collection A collection of matching token records.
     */
    private function getTokensByCaseNumber(string $caseNumber)
    {
        return DB::table('process_request_tokens')
            ->select('id', 'element_id', 'element_name', 'user_id', 'process_id')
            ->whereIn('process_request_id', function ($query) use ($caseNumber) {
                // Subquery to select process_request IDs related to the given case number
                $query->select('id')
                    ->from('process_requests')
                    ->where('case_number', $caseNumber);
            })
            ->get();
    }

    /**
     * Get process JSON data for matching request IDs.
     *
     * @param Illuminate\Support\Collection $matchingRequestIds
     * @return array
     */
    private function getProcessJsonData($matchingRequestIds)
    {
        $jsonData = [];

        if ($matchingRequestIds->isNotEmpty()) {
            foreach ($matchingRequestIds as $detail) {
                $requests = DB::table('process_requests')
                    ->join('processes', 'process_requests.process_id', '=', 'processes.id')
                    ->where('process_requests.id', $detail->id)
                    ->select('processes.id as process_id', 'processes.name as process_name')
                    ->get();
                foreach ($requests as $request) {
                    $jsonData[] = [
                        'id' => $request->process_id,
                        'name' => $request->process_name,
                    ];
                }
            }
        }

        return $jsonData;
    }

    /**
     * Get request data as JSON formatted array from matching request IDs.
     *
     * @param Illuminate\Support\Collection $matchingRequestIds
     * @return array
     */
    private function getRequestJsonData($matchingRequestIds)
    {
        return $matchingRequestIds->isNotEmpty()
            ? $matchingRequestIds->map(function ($request) {
                return [
                    'id' => $request->id,
                    'name' => $request->name,
                    'parent_request_id' => $request->parent_request_id,
                ];
            })->toArray()
            : [];
    }

    /**
     * Get token data as JSON formatted array from matching tokens.
     *
     * @param Illuminate\Support\Collection $matchingRequestTokens
     * @return array
     */
    private function getTokenJsonData($matchingRequestTokens)
    {
        return $matchingRequestTokens->isNotEmpty()
            ? $matchingRequestTokens->pluck('id')->toArray()
            : [];
    }

    /**
     * Get participant data as JSON formatted array from matching tokens, ensuring user_id is not null.
     *
     * @param Illuminate\Support\Collection $matchingRequestTokens
     * @return array
     */
    private function getParticipantJsonData($matchingRequestTokens)
    {
        return $matchingRequestTokens->isNotEmpty()
            ? $matchingRequestTokens->filter(function ($token) {
                return !is_null($token->user_id); // Filter out null user_ids
            })->pluck('user_id')->toArray()
            : [];
    }

    /**
     * Get task data as JSON formatted array from matching tokens.
     *
     * @param Illuminate\Support\Collection $matchingRequestTokens
     * @return array
     */
    private function getTaskJsonData($matchingRequestTokens)
    {
        return $matchingRequestTokens->isNotEmpty()
            ? $matchingRequestTokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'element_id' => $token->element_id,
                    'name' => $token->element_name,
                    'process_id' => $token->process_id,
                ];
            })->toArray()
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
            'case_status' => $request->status,
            'processes' => json_encode($data['processes']),
            'requests' => json_encode($data['requests']),
            'request_tokens' => json_encode($data['tokens']),
            'tasks' => json_encode($data['tasks']),
            'participants' => json_encode($data['participants']),
            'initiated_at' => $request->initiated_at,
            'completed_at' => null,
            'keywords' => $request->case_title,
            'created_at' => now(),
            'updated_at' => now(),
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

        foreach ($requests as $request) {
            // Fetch all related data
            $matchingRequestIds = $this->getMatchingRequestDetails($request->case_number);
            $matchingRequestTokens = $this->getTokensByCaseNumber($request->case_number);

            // Prepare JSON data
            $processJsonData = $this->getProcessJsonData($matchingRequestIds);
            $requestJsonData = $this->getRequestJsonData($matchingRequestIds);
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

        // Insert data into the database in bulk
        if (!empty($inserts)) {
            DB::table('cases_started')->insert($inserts);
        }
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

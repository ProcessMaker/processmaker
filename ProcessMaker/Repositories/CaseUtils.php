<?php

namespace ProcessMaker\Repositories;

use Illuminate\Support\Collection;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class CaseUtils
{
    const ALLOWED_ELEMENT_TYPES = ['task'];

    const ALLOWED_REQUEST_TOKENS = ['task', 'scriptTask', 'callActivity'];

    const CASE_NUMBER_PREFIX = 'cn_';

    /**
     * Get the case number split into keywords.
     * @param int $caseNumber
     * @return string
     */
    public static function getCaseNumberByKeywords(int $caseNumber): string
    {
        $caseNumber = (string) $caseNumber;
        $keywords = array_map(
            fn($i) => self::CASE_NUMBER_PREFIX . substr($caseNumber, 0, $i),
            range(1, strlen($caseNumber))
        );

        return implode(' ', $keywords);
    }

    /**
     * Get the keywords for a case number and additional data.
     * @param array $data
     * @return string
     */
    public static function getKeywords(array $dataKeywords): string
    {
        $keywords = array_map(function ($key, $keyword) {
            return $key === 'case_number' ? self::getCaseNumberByKeywords($keyword) : $keyword;
        }, array_keys($dataKeywords), $dataKeywords);

        return implode(' ', $keywords);
    }

    /**
     * Store processes.
     *
     * @param Collection $processes
     * @param array|null $processData
     *     An optional array of additional process data. Each element in the array should have the following structure:
     *     [
     *         'id' => int,    // The unique identifier of the process
     *         'name' => string  // The name of the process
     *     ]
     * @param string|null $processName
     * @return Collection
     */
    public static function storeProcesses(Collection $processes, ?array $processData = []): Collection
    {
        // check if the process data has the required keys
        if (!empty($processData) && array_key_exists('id', $processData) && array_key_exists('name', $processData)) {
            $processes->push($processData);
        }

        return $processes->unique('id')->values();
    }

    /**
     * Store requests.
     *
     * @param Collection $requests
     * @param array|null $requestData
     *     An optional array of additional request data. Each element in the array should have the following structure:
     *     [
     *         'id' => int,                     // The unique identifier of the request
     *         'name' => string,                // The name of the request
     *         'parent_request_id' => int|null  // The ID of the parent request, or null if there is no parent
     *     ]
     * @return Collection
     */
    public static function storeRequests(Collection $requests, ?array $requestData = []): Collection
    {
        // check if the request data has the required keys
        if (!empty($requestData) && array_key_exists('id', $requestData) && array_key_exists('name', $requestData)) {
            $requests->push($requestData);
        }

        return $requests->unique('id')->values();
    }

    /**
     * Store request tokens.
     *
     * @param Collection $requestTokens
     * @param int|null $tokenId
     * @return Collection
     */
    public static function storeRequestTokens(Collection $requestTokens, ?int $tokenId = null): Collection
    {
        if (!is_null($tokenId)) {
            $requestTokens->push($tokenId);
        }

        return $requestTokens->unique()->values();
    }

    /**
     * Store tasks.
     *
     * @param Collection $tasks
     * @param array|null $taskData
     *     An optional array of additional task data. Each element in the array should have the following structure:
     *     [
     *         'id' => int,         // The unique identifier of the task
     *         'element_id' => int, // The unique identifier of the element
     *         'name' => string,    // The name of the element
     *         'process_id' => int  // The unique identifier of the process
     *         'element_type' => string  // The type of the element
     *     ]
     * @return Collection
     */
    public static function storeTasks(Collection $tasks, ?array $taskData = []): Collection
    {
        $requiredKeys = ['id', 'element_id', 'name', 'process_id', 'element_type'];

        if (
            !empty($taskData) && !array_diff($requiredKeys, array_keys($taskData))
            && in_array($taskData['element_type'], self::ALLOWED_ELEMENT_TYPES)
        ) {
            unset($taskData['element_type']);
            $tasks->push($taskData);
        }

        return $tasks->unique('id')->values();
    }

    /**
     * Store participants.
     *
     * @param Collection $participants
     * @param int|null $participantId
     * @return Collection
     */
    public static function storeParticipants(Collection $participants, ?int $participantId = null): Collection
    {
        if (!is_null($participantId)) {
            $participants->push($participantId);
        }

        return $participants->unique()->values();
    }

    /**
     * Extract data from an object based on a mapping array.
     *
     * @param object $object The object to extract data from.
     * @param array $mapping An associative array where keys are the desired keys in the output array and values are the corresponding properties in the object.
     * @return array The extracted data as an associative array.
     */
    public static function extractData(object $object, array $mapping): array
    {
        $data = [];
        foreach ($mapping as $key => $property) {
            $data[$key] = data_get($object, $property);
        }
        return $data;
    }
}

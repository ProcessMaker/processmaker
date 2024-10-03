<?php

namespace ProcessMaker\Repositories;

use Illuminate\Support\Collection;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class CaseUtils
{
    const ALLOWED_ELEMENT_TYPES = ['task'];

    const ALLOWED_REQUEST_TOKENS = ['task', 'scriptTask', 'callActivity'];

    /**
     * Store processes.
     *
     * @param ExecutionInstanceInterface $instance
     * @param Collection $processes
     * @return Collection
     */
    public static function storeProcesses(ExecutionInstanceInterface $instance, Collection $processes)
    {
        return $processes->push([
            'id' => $instance->process->id,
            'name' => $instance->process->name,
        ])
        ->unique('id')
        ->values();
    }

    /**
     * Store requests.
     *
     * @param ExecutionInstanceInterface $instance
     * @param Collection $requests
     * @return Collection
     */
    public static function storeRequests(ExecutionInstanceInterface $instance, Collection $requests)
    {
        return $requests->push([
            'id' => $instance->id,
            'name' => $instance->name,
            'parent_request_id' => $instance?->parentRequest?->id,
        ])
        ->unique('id')
        ->values();
    }

    /**
     * Store request tokens.
     *
     * @param int $tokenId
     * @param Collection $requestTokens
     * @return Collection
     */
    public static function storeRequestTokens(int $tokenId, Collection $requestTokens)
    {
        return $requestTokens->push($tokenId)
            ->unique()
            ->values();
    }

    /**
     * Store tasks.
     *
     * @param TokenInterface $token
     * @param Collection $tasks
     * @return Collection
     */
    public static function storeTasks(TokenInterface $token, Collection $tasks)
    {
        if (in_array($token->element_type, self::ALLOWED_ELEMENT_TYPES)) {
            return $tasks->push([
                'id' => $token->getKey(),
                'element_id' => $token->element_id,
                'name' => $token->element_name,
                'process_id' => $token->process_id,
            ])
            ->unique('id')
            ->values();
        }

        return $tasks;
    }
}

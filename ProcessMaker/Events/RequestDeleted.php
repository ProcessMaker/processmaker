<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class RequestDeleted implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private int $requestId;

    private ?string $caseNumber;

    private string $name;

    private ?int $processId;

    private ?int $parentRequestId;

    private array $childRequestIds;

    /**
     * Create a new event instance.
     *
     * The request row no longer exists when the listener runs, so plain
     * values are used instead of the model.
     *
     * @return void
     */
    public function __construct(
        int $requestId,
        ?string $caseNumber,
        string $name,
        ?int $processId,
        ?int $parentRequestId,
        array $childRequestIds = []
    ) {
        $this->requestId = $requestId;
        $this->caseNumber = $caseNumber;
        $this->name = $name;
        $this->processId = $processId;
        $this->parentRequestId = $parentRequestId;
        $this->childRequestIds = $childRequestIds;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => $this->name,
            'request_id' => $this->requestId,
            'case_number' => $this->caseNumber,
            'process_id' => $this->processId,
            'parent_request_id' => $this->parentRequestId,
            'child_request_ids' => $this->childRequestIds,
            'deleted_at' => Carbon::now(),
        ];
    }

    /**
     * Get specific changes related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'request_id' => $this->requestId,
            'case_number' => $this->caseNumber,
        ];
    }

    /**
     * Get the Event name
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'RequestDeleted';
    }
}

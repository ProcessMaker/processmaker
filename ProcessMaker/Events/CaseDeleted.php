<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class CaseDeleted implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private string $caseNumber;

    private string $caseTitle;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $caseNumber, string $caseTitle)
    {
        $this->caseNumber = $caseNumber;
        $this->caseTitle = $caseTitle;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => $this->caseTitle,
            'case_number' => $this->caseNumber,
            'deleted_at' => Carbon::now(),
        ];
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
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
        return 'CaseDeleted';
    }
}

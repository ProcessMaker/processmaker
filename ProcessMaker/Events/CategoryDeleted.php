<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessCategory;

class CategoryDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    private ProcessCategory $processCategory;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessCategory $data)
    {
        $this->processCategory = $data;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => $this->processCategory->getAttribute('name'),
            'deleted_at' => Carbon::now(),
        ];
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->processCategory->getAttribute('id'),
            'name' => $this->processCategory->getAttribute('name'),
            'status' => $this->processCategory->getAttribute('status'),
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'CategoryDeleted';
    }
}

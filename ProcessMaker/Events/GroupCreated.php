<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Group;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class GroupCreated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private Group $group;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Group $data)
    {
        $this->group = $data;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => [
                'label' => $this->group->getAttribute('name'),
                'link' => route('groups.edit', $this->group),
            ],
            'description' => $this->group->getAttribute('description'),
            'created_at' => $this->group->getAttribute('created_at'),
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
            'id' => $this->group->getAttribute('id')
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'GroupCreated';
    }
}

<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Group;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class GroupUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private Group $group;

    private array $changes;

    private array $original;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Group $data, array $changes, array $original)
    {
        $this->group = $data;
        $this->changes = $changes;
        $this->original = $original;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge([
            'name' => [
                'label' => $this->group->getAttribute('name'),
                'link' => route('groups.edit', $this->group),
            ],
            'last_modified' => $this->group->getAttribute('updated_at'),
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->group->getAttribute('id'),
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'GroupUpdated';
    }
}

<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class TemplateUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $changes;
    private array $original;
    private bool $processType;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $changes, array $original, bool $processType)
    {
        $this->changes = $changes;
        $this->original = $original;
        $this->processType = $processType;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->processType) {
            return [
                'name' => [
                    'label' => $this->processType
                ],
                'last_modified' => $this->changes['updated_at'] ?? Carbon::now()
            ];
        } else {
            $oldData = array_diff_assoc($this->original, $this->changes);
            $newData = array_diff_assoc($this->changes, $this->original);

            return array_merge([
                'name' => [
                    'label' => $this->processType
                ],
                'last_modified' => $this->changes['updated_at'] ?? Carbon::now()
            ], $this->formatChanges($newData, $oldData));
        }
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'TemplateUpdated';
    }
}

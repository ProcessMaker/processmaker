<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class CustomizeUiUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $data;

    private array $changes;

    private array $original;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $original, array $changes)
    {
        if (isset($original['config'])) {
            $original = $original['config'];
        }
        $changes = array_diff_assoc($changes, $original);
        $original = array_intersect_key($original, $changes);
        $this->original = $original;
        $this->changes = $changes;
        $this->buildData();
    }

    /**
     * Building the data
     */
    public function buildData()
    {
        if (isset($this->changes['variables'])) {
            $varChanges = [];
            $varOriginal = [];
            foreach ((array)json_decode($this->changes['variables'], true) as $variable) {
                $varChanges[$variable['title']] = $variable['value'];
            }
            foreach ((array)json_decode($this->original['variables'], true) as $variable) {
                $varOriginal[$variable['title']] = $variable['value'];
            }
            $varChanges = array_diff($varChanges, $varOriginal);
            $varOriginal = array_intersect_key($varOriginal, $varChanges);
            $this->changes['variables'] = $varOriginal;
            $this->original['variables'] = $varOriginal;
        }
        $this->data = array_merge(
            [
                'name' => [
                    'label' => 'Customize Ui',
                    'link' => route('customize-ui.edit'),
                ],
                'last_modified' => Carbon::now()
            ],
            $this->formatChanges($this->changes, $this->original)
        );
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'CustomizeUiUpdated';
    }
}

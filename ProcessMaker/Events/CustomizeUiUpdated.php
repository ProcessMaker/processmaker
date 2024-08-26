<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\ArrayHelper;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class CustomizeUiUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $data;

    private array $changes;

    private array $original;

    private bool $reset;

    private string $defaultFont = '{"id":"\'Open Sans\'","title":"Default Font"}';

    /**
     * Default color variables
     *
     * @return string
     */
    private function defaultVariables(): string
    {
        $defaults = config('app.default_colors');
        
        $objects = [];
        foreach ($defaults as $name => $value) {
            $objects[] = [
                'id' => '$' . $name,
                'value' => $value,
                'title' => ucfirst($name),
            ];
        }
        
        return json_encode($objects);
    }
    
    /**
     * Create a new event instance.
     *
     * @param array $original
     * @param array $changes
     * @param bool $reset
     *
     * @return void
     */
    public function __construct(array $original, array $changes, $reset = false)
    {
        if (isset($original['config'])) {
            $original = $original['config'];
        }
        $changes = array_diff_assoc($changes, $original);
        $original = array_intersect_key($original, $changes);
        $this->original = $original;
        $this->changes = $changes;
        $this->reset = $reset;
        $this->buildData();
    }

    /**
     * Building the data
     */
    public function buildData()
    {
        if (!isset($this->original['variables'])) {
            $this->original['variables'] = $this->defaultVariables();
        }
        if (!isset($this->changes['variables'])) {
            $this->changes['variables'] = $this->defaultVariables();
        }
        if (isset($this->changes['variables']) && isset($this->original['variables'])) {
            $varChanges = [];
            $varOriginal = [];
            foreach ((array) json_decode($this->changes['variables'], true) as $variable) {
                $varChanges[$variable['title']] = $variable['value'];
            }
            foreach ((array) json_decode($this->original['variables'], true) as $variable) {
                $varOriginal[$variable['title']] = $variable['value'];
            }
            $varChanges = array_diff($varChanges, $varOriginal);
            $varOriginal = array_intersect_key($varOriginal, $varChanges);
            $this->changes['variables'] = $varChanges;
            $this->original['variables'] = $varOriginal;
        }
        // Set a value sansSerifFont
        $this->original['sansSerifFont'] = !isset($this->original['sansSerifFont']) ? $this->defaultFont : $this->original['sansSerifFont'];
        // Define if the action reset was executed
        $actionReset = ($this->reset) ? ['Action' => 'Reset'] : [];
        $this->data = array_merge(
            [
                'name' => [
                    'label' => 'Customize Ui',
                    'link' => route('customize-ui.edit'),
                ],
                'last_modified' => Carbon::now(),
            ],
            $actionReset,
            ArrayHelper::getArrayDifferencesWithFormat($this->changes, $this->original)
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

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
    private bool $reset;
    private string $defaultVariables = '[
        {"id":"$primary","value":"#0872C2","title":"Primary"},
        {"id":"$secondary","value":"#6C757D","title":"Secondary"},
        {"id":"$success","value":"#00875A","title":"Success"},
        {"id":"$info","value":"#104A75","title":"Info"},
        {"id":"$warning","value":"#FFAB00","title":"Warning"},
        {"id":"$danger","value":"#E50130","title":"Danger"},
        {"id":"$dark","value":"#000000","title":"Dark"},
        {"id":"$light","value":"#FFFFFF","title":"Light"}
    ]';
    private string $defaultFont = '{"id":"\'Open Sans\'","title":"Default Font"}';

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
            $this->original['variables'] = $this->defaultVariables;
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
        if (!isset($this->original['sansSerifFont'])) {
            $this->original['sansSerifFont'] = $this->defaultFont;
        }
        if ($this->original['sansSerifFont'] == $this->changes['sansSerifFont']) {
            unset($this->original['sansSerifFont']);
            unset($this->changes['sansSerifFont']);
        }
        if ($this->original['variables'] == $this->changes['sansSevariablesrifFont']) {
            unset($this->original['variables']);
            unset($this->changes['variables']);
        }
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

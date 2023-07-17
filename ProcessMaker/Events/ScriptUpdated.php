<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ScriptUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $changes;

    private array $original;

    private Script $script;

    /**
     * Create a new event instance.
     *
     * @param Script $script
     * @param array $changes
     * @param array $original
     */
    public function __construct(Script $script, array $changes, array $original)
    {
        $this->script = $script;
        $this->changes = $changes;
        $this->original = $original;

        // Get category name
        $this->original['script_category'] = isset($original['script_category_id'])
        ? ScriptCategory::getNamesByIds($this->original['script_category_id']) : '';
        unset($this->original['script_category_id']);
        $this->changes['script_category'] = isset($changes['script_category_id'])
        ? ScriptCategory::getNamesByIds($this->changes['script_category_id']) : '';
        unset($this->changes['script_category_id']);
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'script_id' => $this->script->id,
        ];
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        unset($this->changes['code']);
        unset($this->original['code']);

        $linkName = [
            'label' => $this->script->getAttribute('title'),
            'link' => route('scripts.index'),
        ];

        return array_merge(
            [
                'name' => $linkName,
                'script_name' => $this->script->getAttribute('title'),
                'last_modified' => $this->script->getAttribute('updated_at'),
            ],
            $this->formatChanges($this->changes, $this->original)
        );
    }

    public function getEventName(): string
    {
        return 'ScriptUpdated';
    }
}

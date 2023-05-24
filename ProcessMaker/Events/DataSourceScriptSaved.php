<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\SensitiveDataHelper;
use ProcessMaker\Packages\Connectors\DataSources\Models\Script as DataSourceScript;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class DataSourceScriptSaved implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private array $changes;
    private array $original;
    private DataSourceScript $dataSourceScript;

    /**
     * Create a new event instance.
     *
     * @param DataSourceScript $dataSourceScript
     * @param array $changes
     * @param array $original
     */
    public function __construct(DataSourceScript $dataSourceScript, array $changes, array $original)
    {
        $this->changes = SensitiveDataHelper::parseArray($changes);
        $this->original = SensitiveDataHelper::parseArray($original);
        $this->dataSourceScript = $dataSourceScript;
    }

    public function getChanges(): array
    {
        return array_merge([
            'script_id' => $this->dataSourceScript->script_id
        ], $this->changes);
    }

    public function getData(): array
    {
        $basic = [
            'Name' => $this->dataSourceScript->script->getAttribute('title'),
        ];
        return array_merge($basic, $this->formatChanges($this->changes, $this->original));
    }

    public function getEventName(): string
    {
        return 'ScriptUpdated';
    }
}

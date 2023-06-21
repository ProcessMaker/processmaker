<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\SensitiveDataHelper;
use ProcessMaker\Models\Setting;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class SettingsUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;
    public const SENSITIVE_KEYS = [
        'password',
    ];

    private Setting $setting;

    private array $changes;
    private array $original;

    /**
     * Create a new event instance.
     *
     * @param Setting $setting
     * @param array $changes
     */
    public function __construct(Setting $setting, array $changes, array $original)
    {
        $this->setting = $setting;
        $this->changes = $changes;
        $this->original = $original;
        // Some configuration are related to the password
        $attribute = strtolower($this->setting->getAttribute('name'));
        if (array_keys($this::SENSITIVE_KEYS, $attribute)) {
            $this->changes[$attribute] = $this->changes['config'];
            $this->original[$attribute] = $this->original['config'];
            unset($this->changes['config']);
            unset($this->original['config']);
        }
        // Verify if the config is a sensitive value
        $key = $this->setting->getAttribute('key');
        if (SensitiveDataHelper::isSensitiveKey($key)) {
            $this->changes['config'] = SensitiveDataHelper::parseString($this->changes['config']);
            $this->original['config'] = SensitiveDataHelper::parseString($this->original['config']);
        }
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return array_merge([
            'setting_id' => $this->setting->id
        ], $this->changes);
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge([
            'group' => $this->setting->getAttribute('group'),
            'name' => $this->setting->getAttribute('name'),
        ], $this->formatChanges($this->changes, $this->original));
    }

     /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getEventName(): string
    {
        return 'SettingsUpdated';
    }
}

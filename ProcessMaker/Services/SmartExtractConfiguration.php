<?php

namespace ProcessMaker\Services;

use ProcessMaker\Models\EnvironmentVariable;

class SmartExtractConfiguration
{
    public const SEND_DOCUMENT_SCRIPT_KEY = 'package-smart-extract/document-send';

    public const API_HOST = 'SMART_EXTRACT_API_HOST';

    public const CLIENT_ID = 'SMART_EXTRACT_CLIENT_ID';

    public const CLIENT_SECRET = 'SMART_EXTRACT_CLIENT_SECRET';

    public const DASHBOARD_URL = 'SMART_EXTRACT_DASHBOARD_URL';

    public const HITL_ENABLED = 'SMART_EXTRACT_HITL_ENABLED';

    private const CONFIG_KEYS = [
        self::API_HOST => 'smart-extract.api_host',
        self::CLIENT_ID => 'smart-extract.client_id',
        self::CLIENT_SECRET => 'smart-extract.client_secret',
        self::DASHBOARD_URL => 'smart-extract.dashboard_url',
        self::HITL_ENABLED => 'smart-extract.hitl_enabled',
    ];

    private ?array $values = null;

    public function apiHost(): ?string
    {
        return $this->stringValue(self::API_HOST);
    }

    public function clientId(): ?string
    {
        return $this->stringValue(self::CLIENT_ID);
    }

    public function clientSecret(): ?string
    {
        return $this->stringValue(self::CLIENT_SECRET);
    }

    public function dashboardUrl(): ?string
    {
        return $this->stringValue(self::DASHBOARD_URL);
    }

    public function hitlEnabled(): bool
    {
        $value = $this->value(self::HITL_ENABLED);

        if (is_bool($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return false;
        }

        return filter_var(trim($value), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function stringValue(string $name): ?string
    {
        $value = $this->value($name);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function value(string $name): mixed
    {
        $values = $this->values();

        if (array_key_exists($name, $values)) {
            return $values[$name];
        }

        return config(self::CONFIG_KEYS[$name]);
    }

    private function values(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $this->values = EnvironmentVariable::query()
            ->whereIn('name', [
                self::API_HOST,
                self::CLIENT_ID,
                self::CLIENT_SECRET,
                self::DASHBOARD_URL,
                self::HITL_ENABLED,
            ])
            ->get()
            ->mapWithKeys(fn (EnvironmentVariable $variable) => [
                $variable->name => $variable->value,
            ])
            ->all();

        return $this->values;
    }
}

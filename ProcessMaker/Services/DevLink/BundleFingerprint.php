<?php

namespace ProcessMaker\Services\DevLink;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleSetting;

class BundleFingerprint
{
    private const PREFIX = 'v1:';

    private const AUDIT_KEYS = [
        'last_modified_by',
        'last_modified_by_id',
    ];

    private const TIMESTAMP_KEYS = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function calculate(Bundle $bundle): string
    {
        $payload = [
            'assets' => $this->assetPayloads($bundle),
            'settings' => $this->settingPayloads($bundle),
        ];

        return self::PREFIX . hash('sha256', json_encode(
            $this->normalize($payload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function assetPayloads(Bundle $bundle): array
    {
        $payloads = array_map(
            fn ($payload) => $this->normalize($payload),
            $bundle->export(),
        );

        usort(
            $payloads,
            fn ($left, $right) => $this->payloadSortKey($left) <=> $this->payloadSortKey($right),
        );

        return $payloads;
    }

    private function settingPayloads(Bundle $bundle): array
    {
        return $bundle->settings()
            ->orderBy('setting')
            ->orderBy('id')
            ->get()
            ->map(function (BundleSetting $setting) {
                return [
                    'setting' => $setting->setting,
                    'config' => $this->normalizeSettingConfig($setting->config),
                    'payloads' => $this->normalizeSettingPayloads($setting->export()),
                ];
            })
            ->all();
    }

    private function normalizeSettingConfig(mixed $config): mixed
    {
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $config = $decoded;
            }
        }

        if (is_array($config) && isset($config['id']) && is_array($config['id'])) {
            sort($config['id']);
        }

        return $this->normalize($config);
    }

    private function normalizeSettingPayloads(mixed $payloads): mixed
    {
        $payloads = $this->normalize($payloads);

        if (!is_array($payloads) || !array_is_list($payloads)) {
            return $payloads;
        }

        usort(
            $payloads,
            fn ($left, $right) => $this->payloadSortKey($left) <=> $this->payloadSortKey($right),
        );

        return $payloads;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        } elseif ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if (!is_array($value)) {
            return $value;
        }

        $isExporterNode = isset($value['exporter'], $value['attributes']);
        $isModelRecord = array_key_exists('created_at', $value) && array_key_exists('updated_at', $value);
        $normalized = [];

        foreach ($value as $key => $item) {
            if ($isExporterNode && in_array($key, self::AUDIT_KEYS, true)) {
                continue;
            }

            if ($isModelRecord && in_array($key, self::TIMESTAMP_KEYS, true)) {
                continue;
            }

            if ($key === 'attributes' && is_array($item)) {
                $item = array_diff_key($item, array_flip(self::TIMESTAMP_KEYS));
            }

            $normalized[$key] = $this->normalize($item);
        }

        if (!array_is_list($normalized)) {
            ksort($normalized, SORT_STRING);
        }

        return $normalized;
    }

    private function payloadSortKey(mixed $payload): string
    {
        if (!is_array($payload)) {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        }

        if (isset($payload['type'], $payload['root'])) {
            return $payload['type'] . '|' . $payload['root'];
        }

        if (isset($payload['key'])) {
            return (string) $payload['key'];
        }

        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}

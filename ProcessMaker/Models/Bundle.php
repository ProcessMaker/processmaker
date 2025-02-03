<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Http;
use ProcessMaker\Exception\ExporterNotSupported;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Bundle extends ProcessMakerModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected $appends = ['asset_count'];

    protected $casts = [
        'published' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function scopeEditable($query)
    {
        return $query->where('dev_link_id', null);
    }

    public function editable() : bool
    {
        return $this->dev_link_id === null;
    }

    public function assets()
    {
        return $this->hasMany(BundleAsset::class);
    }

    public function settings()
    {
        return $this->hasMany(BundleSetting::class);
    }

    public function devLink()
    {
        return $this->belongsTo(DevLink::class, 'dev_link_id');
    }

    public function getAssetCountAttribute()
    {
        return $this->assets()->count();
    }

    public function export()
    {
        $exports = [];

        foreach ($this->assets as $bundleAsset) {
            $asset = $bundleAsset->asset;

            if (!BundleAsset::canExport($asset)) {
                throw new ExporterNotSupported();
            }

            $exports[] = $asset->export();
        }

        return $exports;
    }

    public function exportSettings()
    {
        $exports = [];

        foreach ($this->settings as $setting) {
            $exports[] = $setting;
        }

        return $exports;
    }

    public function exportSettingPayloads()
    {
        return $this->settings()->get()->map(function ($setting) {
            return $setting->export();
        });
    }

    public function syncAssets($assets)
    {
        $assetKeys = [];
        foreach ($assets as $asset) {
            $assetKeys[BundleAsset::makeKey($asset)] = true;
        }

        $existingKeys = [];
        foreach ($this->assets as $bundleAsset) {
            if (!isset($assetKeys[$bundleAsset->key])) {
                // Removing bundleAsset because it is associated but not passed to this method
                $bundleAsset->delete();
                continue;
            }

            $existingKeys[$bundleAsset->key] = true;
        }

        foreach ($assets as $asset) {
            if (isset($existingKeys[BundleAsset::makeKey($asset)])) {
                // Ignoring because it is already associated
                continue;
            }
            // Creating because it is not associated yet
            BundleAsset::create([
                'bundle_id' => $this->id,
                'asset_type' => $asset::class,
                'asset_id' => $asset->id,
            ]);
        }

        $this->refresh();
    }

    public function addAsset(ProcessMakerModel $asset)
    {
        if (!BundleAsset::canExport($asset)) {
            throw new ExporterNotSupported();
        }

        $this->validateEditable();

        $exists = $this->assets()->where('asset_type', get_class($asset))->where('asset_id', $asset->id)->exists();
        if ($exists) {
            throw ValidationException::withMessages(['*' => 'Asset already exists in bundle']);
        }

        BundleAsset::create([
            'bundle_id' => $this->id,
            'asset_type' => get_class($asset),
            'asset_id' => $asset->id,
        ]);
    }

    public function addSettings($setting, $newId, $type = null)
    {
        $existingSetting = $this->settings()->where('setting', $setting)->first();
        //If $newId is null, set config to null
        if (is_null($newId) && is_null($type)) {
            if ($existingSetting) {
                $existingSetting->update(['config' => null]);
            } else {
                BundleSetting::create([
                    'bundle_id' => $this->id,
                    'setting' => $setting,
                    'config' => null,
                ]);
            }

            return;
        }
        // verify if newId is a json with id key
        $decodedNewId = json_decode($newId, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decodedNewId)) {
                $newId = $decodedNewId;
                if ($existingSetting) {
                    if ($decodedNewId['id'] === []) {
                        $existingSetting->delete();

                        return;
                    }
                    $existingSetting->update(['config' => json_encode($newId)]);

                    return;
                }
            } elseif (isset($decodedNewId['id'])) {
                $newId = [$decodedNewId['id']];
            } else {
                $newId = ['id' => [$decodedNewId]];
            }
        } else {
            $newId = ['id' => [$newId]];
        }

        if ($existingSetting) {
            // Decode the existing JSON
            $config = json_decode($existingSetting->config, true);
            // Ensure 'id' is an array
            if (!isset($config['id']) || !is_array($config['id'])) {
                $config['id'] = [];
            }
            // Add the new ID
            foreach ($newId['id'] as $id) {
                $config['id'][] = $id;
            }
            // Remove duplicates
            $config['id'] = array_unique($config['id']);
            //reindex the array
            $config['id'] = array_values($config['id']);
            // Update the config
            $existingSetting->update([
                'config' => json_encode($config),
            ]);
        } else {
            // Create a new BundleSetting with the initial ID
            $config = ['id' => []];
            if ($newId && $type !== 'settings') {
                foreach ($newId['id'] as $id) {
                    $config['id'][] = $id;
                }
            }

            if ($type === 'settings') {
                $settingsMenu = SettingsMenus::where('menu_group', $setting)->first();
                $settingsKeys = Setting::where([
                    ['group_id', '=', $settingsMenu->id],
                    ['hidden', '=', false],
                ])->pluck('key')->toArray();
                if ($settingsMenu) {
                    $config['id'] = $settingsKeys;
                    $config['type'] = $type;
                }
            }

            if ($type === 'ui_settings') {
                $config['id'] = ['css-override', 'login-footer', 'logo-alt-text'];
                $config['type'] = $type;
            }

            BundleSetting::create([
                'bundle_id' => $this->id,
                'setting' => $setting,
                'config' => json_encode($config),
            ]);
        }
    }

    public function addAssetToBundles(ProcessMakerModel $asset)
    {
        $message = null;
        try {
            $this->addAsset($asset);
        } catch (ValidationException $ve) {
            $message = $ve->getMessage();
        }

        return $message;
    }

    public function addSettingToBundles($setting, $newId, $type = null)
    {
        $message = null;
        try {
            $this->addSettings($setting, $newId, $type);
        } catch (ValidationException $ve) {
            $message = $ve->getMessage();
        }

        return $message;
    }

    public function validateEditable()
    {
        if (!$this->editable()) {
            throw ValidationException::withMessages(['*' => 'Bundle is not editable']);
        }
    }

    public function filesSortedByVersion()
    {
        return $this->getMedia()->map(function ($media) {
            return [
                $media,
                $media->getCustomProperty('version'),
            ];
        })->sort(function ($a, $b) {
            // newest versions first
            return version_compare($a[1], $b[1]) * -1;
        })->map(function ($item) {
            return $item[0];
        });
    }

    public function newestVersionFile()
    {
        return $this->filesSortedByVersion()->first();
    }

    public function savePayloadsToFile(array $payloads, array $payloadsSettings, $logger = null)
    {
        if ($logger === null) {
            $logger = new Logger();
        }
        $logger->status('Saving the bundle locally');
        $payloads = array_merge($payloads, $payloadsSettings[0]);

        $this->addMediaFromString(
            gzencode(
                json_encode($payloads)
            ),
        )->usingFileName('payloads.json.gz')
        ->withCustomProperties(['version' => $this->version])
        ->toMediaCollection();

        // Keep only the 3 most recent versions
        $count = 0;
        foreach ($this->filesSortedByVersion() as $media) {
            if ($count >= 3) {
                $media->delete();
            }
            $count++;
        }
    }

    public function installSettings($settings)
    {
        $newSettingsKeys = collect($settings)->pluck('setting')->toArray();

        $this->settings()
            ->whereNotIn('setting', $newSettingsKeys)
            ->delete();

        foreach ($settings as $setting) {
            $this->addSettings($setting['setting'], $setting['config']);
        }
    }

    public function installSettingsPayloads(array $payloads, $mode, $logger = null)
    {
        $options = new Options([
            'mode' => $mode,
        ]);
        $clientRepository = app('Laravel\Passport\ClientRepository');

        $assets = [];
        foreach ($payloads as $payload) {
            if (isset($payload[0]['export'])) {
                $logger->status('Installing bundle settings on the this instance');
                $logger->setSteps($payloads[0]);
                $assets[] = DevLink::import($payload[0], $options, $logger);
            } elseif (isset($payload[0]['setting_type'])) {
                foreach ($payload as $setting) {
                    switch ($setting['setting_type']) {
                        case 'auth_clients':
                            $clientRepository->create(
                                null,
                                $setting['name'],
                                $setting['redirect'],
                                $setting['provider'],
                                $setting['personal_access_client'],
                                $setting['password_client']
                            );
                            break;
                        case 'User Settings':
                        case 'Email':
                        case 'Integrations':
                        case 'Log-In & Auth':
                            $settingsMenu = SettingsMenus::where('menu_group', $setting['setting_type'])->first();
                            Setting::updateOrCreate([
                                'key' => $setting['key'],
                            ], [
                                'config' => $setting['config'],
                                'name' => $setting['name'],
                                'helper' => $setting['helper'],
                                'format' => $setting['format'],
                                'hidden' => $setting['hidden'],
                                'readonly' => $setting['readonly'],
                                'ui' => $setting['ui'],
                                'group_id' => $settingsMenu->id,
                                'group' => $setting['group'],
                            ]);
                            break;
                        case 'ui_settings':
                            Setting::updateOrCreate([
                                'key' => $setting['key'],
                            ], [
                                'config' => $setting['config'],
                                'name' => $setting['name'],
                                'helper' => $setting['helper'],
                                'format' => $setting['format'],
                                'hidden' => $setting['hidden'],
                                'readonly' => $setting['readonly'],
                                'ui' => $setting['ui'],
                            ]);
                            break;
                    }
                }
            }
        }
    }

    public function install(array $payloads, $mode, $logger = null, $reinstall = false)
    {
        if ($logger === null) {
            $logger = new Logger();
        }

        $logger->status('Installing bundle on the this instance');
        $logger->setSteps($payloads);

        $options = new Options([
            'mode' => $mode,
        ]);
        $assets = [];
        foreach ($payloads as $payload) {
            $assets[] = DevLink::import($payload, $options, $logger);
        }

        if ($mode === 'update' && $reinstall === false) {
            $logger->status('Syncing bundle assets');
            $this->syncAssets($assets);
        }
    }

    public function reinstall(string $mode, Logger $logger = null)
    {
        $media = $this->newestVersionFile();

        $content = file_get_contents($media->getPath());
        $payloads = json_decode(gzdecode($content), true);
        $this->install($payloads, $mode, $logger, true);

        $logger?->setStatus('done');
    }

    public function notifyBundleUpdated()
    {
        $bundleInstances = BundleInstance::where('bundle_id', $this->id)->get();
        foreach ($bundleInstances as $bundleInstance) {
            $url = $bundleInstance->instance_url;

            try {
                $response = Http::post($url);

                if ($response->status() === 403) {
                    \Log::error("Failed to notify bundle update for URL: $url " . $response);
                }
            } catch (\Exception $e) {
                \Log::error('Error notifying bundle update: ' . $e->getMessage());
            }
        }
    }
}

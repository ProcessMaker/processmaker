<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        // verify if newId is a json with id key
        $decodedNewId = json_decode($newId, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decodedNewId['id'])) {
            $newId = $decodedNewId['id'];
        }

        if ($existingSetting) {
            // If the config is null, do not add the new ID
            if (is_null($existingSetting->config)) {
                return;
            }

            // Decode the existing JSON
            $config = json_decode($existingSetting->config, true);

            // Ensure 'id' is an array
            if (!isset($config['id']) || !is_array($config['id'])) {
                $config['id'] = [];
            }

            // Add the new ID
            $config['id'][] = $newId;

            // Remove duplicates
            $config['id'] = array_unique($config['id']);

            // Update the config
            $existingSetting->update([
                'config' => json_encode($config),
            ]);
        } else {
            // Create a new BundleSetting with the initial ID
            if ($newId === null && $type === null) {
                BundleSetting::create([
                    'bundle_id' => $this->id,
                    'setting' => $setting,
                    'config' => null,
                ]);
            } else {
                $config = ['id' => []];
                if ($newId && $type !== 'settings') {
                    $config['id'][] = $newId;
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

                BundleSetting::create([
                    'bundle_id' => $this->id,
                    'setting' => $setting,
                    'config' => json_encode($config),
                ]);
            }
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

    public function savePayloadsToFile(array $payloads)
    {
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
            if (isset($payload['export'])) {
                $logger->status('Installing bundle settings on the this instance');
                $logger->setSteps($payloads);
                $assets[] = DevLink::import($payload, $options, $logger);
            } else {
                switch ($payload['setting_type']) {
                    case 'auth_clients':
                        $clientRepository->create(
                            null,
                            $payload['name'],
                            $payload['redirect'],
                            $payload['provider'],
                            $payload['personal_access_client'],
                            $payload['password_client']
                        );
                        break;
                    case 'User Settings':
                    case 'Email':
                    case 'Integrations':
                    case 'Log-In & Auth':
                        $settingsMenu = SettingsMenus::where('menu_group', $payload['setting_type'])->first();
                        foreach ($payload['id'] as $key) {
                            Setting::updateOrCreate([
                                'key' => $key,
                            ], [
                                'config' => $payload['config'],
                                'name' => $payload['name'],
                                'helper' => $payload['helper'],
                                'format' => $payload['format'],
                                'hidden' => $payload['hidden'],
                                'read_only' => $payload['read_only'],
                                'ui' => $payload['ui'],
                                'group' => $payload['group'],
                                'group_id' => $settingsMenu->id,
                            ]);
                        }
                        break;
                }
            }
        }
    }

    public function install(array $payloads, $mode, $logger = null)
    {
        if ($logger === null) {
            $logger = new Logger();
        }

        $logger->status('Saving the bundle locally');
        $this->savePayloadsToFile($payloads);

        $logger->status('Installing bundle on the this instance');
        $logger->setSteps($payloads);

        $options = new Options([
            'mode' => $mode,
        ]);
        $assets = [];
        foreach ($payloads as $payload) {
            $assets[] = DevLink::import($payload, $options, $logger);
        }

        if ($mode === 'update') {
            $logger->status('Syncing bundle assets');
            $this->syncAssets($assets);
        }
    }

    public function reinstall(string $mode, Logger $logger = null)
    {
        $media = $this->newestVersionFile();

        $content = file_get_contents($media->getPath());
        $payloads = json_decode(gzdecode($content), true);

        $this->install($payloads, $mode, $logger);

        $logger?->setStatus('done');
    }
}

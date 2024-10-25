<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Exception\ExporterNotSupported;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\ProcessMakerModel;
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

        $logger->setStatus('done');
    }
}

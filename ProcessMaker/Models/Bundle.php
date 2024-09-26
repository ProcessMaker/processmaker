<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Exception\ExporterNotSupported;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\Models\ProcessMakerModel;

class Bundle extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['asset_count'];

    protected $casts = [
        'locked' => 'boolean',
        'published' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('published', true);
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

        BundleAsset::create([
            'bundle_id' => $this->id,
            'asset_type' => get_class($asset),
            'asset_id' => $asset->id,
        ]);
    }
}

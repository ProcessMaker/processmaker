<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Enums\ExporterMap;

class BundleAsset extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['name', 'url', 'type'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($bundleAsset) {
            $bundleAsset->bundle->validateEditable();
        });
    }

    public static function canExport(ProcessMakerModel $asset)
    {
        return method_exists($asset, 'export') && ExporterMap::getExporterClassForModel($asset);
    }

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    public function asset()
    {
        return $this->morphTo();
    }

    public function getKeyAttribute()
    {
        return $this->asset_type . '-' . $this->asset_id;
    }

    public static function makeKey(ProcessMakerModel $asset)
    {
        return $asset::class . '-' . $asset->id;
    }

    public function getNameAttribute()
    {
        if (
            $this->asset_type === Screen::class ||
            $this->asset_type === Script::class
        ) {
            return $this->title;
        }

        return $this->asset->name;
    }

    public function getUrlAttribute()
    {
        switch($this->asset_type) {
            case Screen::class:
                return "/designer/screen-builder/{$this->asset_id}/edit";
            case Script::class:
                return "/designer/scripts/{$this->asset_id}/builder";
            case Process::class:
                return "/modeler/{$this->asset_id}";
            default:
                return null;
        }
    }

    public function getTypeAttribute()
    {
        switch($this->asset_type) {
            case Screen::class:
                return 'Screen';
            case Script::class:
                return 'Script';
            case Process::class:
                return 'Process';
            default:
                return null;
        }
    }
}

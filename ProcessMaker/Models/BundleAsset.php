<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Enums\ExporterMap;

class BundleAsset extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = ['id'];

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
}

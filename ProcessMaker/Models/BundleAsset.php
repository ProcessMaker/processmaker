<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Exception\ExporterNotSupported;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\ImportExport\Exporters\ScriptExporter;

class BundleAsset extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = ['id'];

    private static $supportedExporters = [
        Process::class => ProcessExporter::class,
        Screen::class => ScreenExporter::class,
        Script::class => ScriptExporter::class,
    ];

    public function exporterClass()
    {
        if (self::hasExporter($this->asset_type)) {
            return $this->getExporterClass();
        } else {
            throw new ExporterNotSupported();
        }
    }

    public static function hasExporter(string $type)
    {
        return array_key_exists($type, self::$supportedExporters);
    }

    public function getExporterClass()
    {
        return $this->supportedExporters[$this->asset_type];
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

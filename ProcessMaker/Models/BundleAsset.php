<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use ProcessMaker\Enums\ExporterMap;

class BundleAsset extends ProcessMakerModel
{
    use HasFactory;

    public const INTEGRITY_VALID = 'valid';

    public const INTEGRITY_MISSING = 'missing';

    public const INTEGRITY_TYPE_UNAVAILABLE = 'type_unavailable';

    protected $guarded = ['id'];

    protected $appends = ['name', 'url', 'type', 'owner_name', 'categories', 'integrity_status'];

    const DATA_SOURCE_CLASS = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSource';

    const COLLECTION_CLASS = 'ProcessMaker\Plugins\Collections\Models\Collection';

    const DECISION_TABLE_CLASS = 'ProcessMaker\Package\PackageDecisionEngine\Models\DecisionTable';

    const FLOW_GENIE_CLASS = 'ProcessMaker\Package\PackageAi\Models\FlowGenie';

    const PM_BLOCK_CLASS = 'ProcessMaker\Package\PackagePmBlocks\Models\PmBlock';

    public static function canExport(?ProcessMakerModel $asset)
    {
        return $asset !== null
            && method_exists($asset, 'export')
            && ExporterMap::getExporterClassForModel($asset);
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
        $asset = $this->resolvedAsset();
        if ($asset === null) {
            return __('Missing :type #:id', [
                'type' => $this->typeLabel(),
                'id' => $this->asset_id,
            ]);
        }

        if (
            $this->asset_type === Screen::class ||
            $this->asset_type === Script::class
        ) {
            return $asset->title;
        }

        return $asset->name;
    }

    public function getUrlAttribute()
    {
        if ($this->integrity_status !== self::INTEGRITY_VALID) {
            return null;
        }

        switch($this->asset_type) {
            case Screen::class:
                return "/designer/screen-builder/{$this->asset_id}/edit";
            case Script::class:
                return "/designer/scripts/{$this->asset_id}/builder";
            case Process::class:
                return "/modeler/{$this->asset_id}";
            case self::DATA_SOURCE_CLASS:
                return "/designer/data-sources/{$this->asset_id}/edit";
            case self::COLLECTION_CLASS:
                return "/collections/{$this->asset_id}/edit";
            case self::DECISION_TABLE_CLASS:
                return "/designer/decision-tables/table-builder/{$this->asset_id}/edit";
            case self::FLOW_GENIE_CLASS:
                return "/designer/flow-genies/{$this->asset_id}/edit";
            case self::PM_BLOCK_CLASS:
                return "/designer/pm-blocks/{$this->asset_id}/edit";
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
            case self::DATA_SOURCE_CLASS:
                return 'data_source';
            case self::COLLECTION_CLASS:
                return 'collection';
            case self::DECISION_TABLE_CLASS:
                return 'decision_table';
            case self::FLOW_GENIE_CLASS:
                return 'flow_genie';
            case self::PM_BLOCK_CLASS:
                return 'pm_block';
            default:
                return null;
        }
    }

    public function getOwnerNameAttribute()
    {
        $asset = $this->resolvedAsset();
        if ($asset && method_exists($asset, 'user') && $asset->user) {
            return $asset->user->firstname . ' ' . $asset->user->lastname;
        }

        return null;
    }

    public function getCategoriesAttribute()
    {
        if ($this->asset_type === self::COLLECTION_CLASS) {
            return [];
        }

        $asset = $this->resolvedAsset();
        if ($asset && method_exists($asset, 'categories')) {
            return $asset->categories->pluck('name')->toArray();
        }

        return [];
    }

    public function getIntegrityStatusAttribute(): string
    {
        if (!class_exists($this->asset_type)) {
            return self::INTEGRITY_TYPE_UNAVAILABLE;
        }

        return $this->resolvedAsset() === null
            ? self::INTEGRITY_MISSING
            : self::INTEGRITY_VALID;
    }

    public function integrityDetails(): array
    {
        return [
            'bundle_asset_id' => $this->id,
            'asset_type' => $this->asset_type,
            'asset_id' => $this->asset_id,
            'integrity_status' => $this->integrity_status,
        ];
    }

    private function resolvedAsset(): ?ProcessMakerModel
    {
        if (!class_exists($this->asset_type)) {
            return null;
        }

        return $this->asset;
    }

    private function typeLabel(): string
    {
        $type = $this->type ?? class_basename($this->asset_type);

        return Str::headline($type);
    }
}

<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Enums\ExporterMap;

class BundleAsset extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['name', 'url', 'type', 'owner_name', 'categories'];

    const DATA_SOURCE_CLASS = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSource';

    const COLLECTION_CLASS = 'ProcessMaker\Plugins\Collections\Models\Collection';

    const DECISION_TABLE_CLASS = 'ProcessMaker\Package\PackageDecisionEngine\Models\DecisionTable';

    const FLOW_GENIE_CLASS = 'ProcessMaker\Package\PackageAi\Models\FlowGenie';

    const PM_BLOCK_CLASS = 'ProcessMaker\Package\PackagePmBlocks\Models\PmBlock';

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
            return $this->asset->title;
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
        if ($this->asset && method_exists($this->asset, 'user')) {
            return $this->asset->user->firstname . ' ' . $this->asset->user->lastname;
        }

        return null;
    }

    public function getCategoriesAttribute()
    {
        if ($this->asset_type === self::COLLECTION_CLASS) {
            return [];
        }

        if ($this->asset && method_exists($this->asset, 'categories')) {
            return $this->asset->categories->pluck('name')->toArray();
        }

        return [];
    }
}

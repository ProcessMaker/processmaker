<?php

namespace ProcessMaker\Traits;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use ProcessMaker\Exception\ProjectAssetSyncException;

trait ProjectAssetTrait
{
    const PROJECT_MODEL_CLASS = 'ProcessMaker\Package\Projects\Models\Project';

    const PROJECT_ASSET_MODEL_CLASS = 'ProcessMaker\Package\Projects\Models\ProjectAsset';

    public function syncProjectAsset($request, $assetModelClass)
    {
        if (class_exists(self::PROJECT_ASSET_MODEL_CLASS) && $request->has('projects')) {
            $projectIds = $request->input('projects', '');

            if (!empty($projectIds)) {
                // Check if the string is in the JSON array format
                $decodedProjects = json_decode($projectIds, true);
                if (is_array($decodedProjects)) {
                    $projectIds = implode(',', array_column($decodedProjects, 'id'));
                } else {
                    $projectIds = trim($projectIds, ',');
                }

                // Explode the comma-separated string, filter, and convert to integers
                $projectIds = array_map('intval', array_filter(explode(',', $projectIds)));
            }

            try {
                // Sync the project assets with the prepared project IDs
                $this->projectAssets()->syncWithPivotValues($projectIds, ['asset_type' => $assetModelClass]);
                \Log::debug('Synced project assets', ['projectIds' => $projectIds]);
            } catch (Exception $e) {
                throw new ProjectAssetSyncException('Error syncing project assets: ' . $e->getMessage());
            }
        }
    }

    public function getProjectsAttribute()
    {
        if (class_exists(self::PROJECT_ASSET_MODEL_CLASS)) {
            $projectAssets = self::PROJECT_ASSET_MODEL_CLASS::where('asset_id', $this->id)
                ->where('asset_type', get_class($this))
                ->get();

            return json_encode($projectAssets->map(function ($projectAsset) {
                return $projectAsset->project;
            }));
        }

        return json_encode([]);
    }
}

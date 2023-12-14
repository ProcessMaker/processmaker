<?php

namespace ProcessMaker\Traits;

use Exception;
use Illuminate\Http\Request;
use ProcessMaker\Exception\ProjectAssetSyncException;

trait ProjectAssetTrait
{
    const PROJECT_MODEL_CLASS = 'ProcessMaker\Package\Projects\Models\Project';

    const PROJECT_ASSET_MODEL_CLASS = 'ProcessMaker\Package\Projects\Models\ProjectAsset';

    public function syncProjectAsset($requestOrInteger, $assetModelClass)
    {
        if (!class_exists(self::PROJECT_ASSET_MODEL_CLASS)) {
            return;
        }

        $projectIds = $this->extractProjectIds($requestOrInteger);

        try {
            // Sync the project assets with the prepared project IDs
            $this->projectAssets()->syncWithPivotValues($projectIds, ['asset_type' => $assetModelClass]);
        } catch (Exception $e) {
            throw new ProjectAssetSyncException('Error syncing project assets: ' . $e->getMessage());
        }
    }

    /**
     * Extract project IDs from the provided input.
     *
     * @param mixed $input
     * @return array
     */
    private function extractProjectIds($input)
    {
        if ($input instanceof Request && $input->has('projects')) {
            $projectIds = $input->input('projects', '');
        } elseif (is_int($input)) {
            $projectIds = $input;
        } else {
            return [];
        }

        if (is_string($projectIds)) {
            $projectIds = $this->convertStringToArray($projectIds);
        }

        return $projectIds;
    }

    /**
     * Convert a string to an array, handling JSON decoding.
     *
     * @param string $input
     * @return array
     */
    private function convertStringToArray($input)
    {
        $decodedProjects = json_decode($input, true);
        if (is_array($decodedProjects)) {
            return array_column($decodedProjects, 'id');
        }

        return array_map('intval', array_filter(explode(',', trim($input, ','))));
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

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
        // Check if the project asset model class exists
        if (!class_exists(self::PROJECT_ASSET_MODEL_CLASS)) {
            return;
        }

        // Extract project IDs from the given input
        $projectIds = $this->extractProjectIds($requestOrInteger);

        try {
            // Sync the project assets with the prepared project IDs
            $this->syncProjectAssets($projectIds, $assetModelClass);
        } catch (Exception $e) {
            throw new ProjectAssetSyncException('Error syncing project assets: ' . $e->getMessage());
        }
    }

    /**
     * Extract project IDs from the given input.
     *
     * @param Request|int|array $requestOrInteger
     * @return array
     */
    private function extractProjectIds($requestOrInteger)
    {
        $projectIds = [];

        if ($requestOrInteger instanceof Request && $requestOrInteger->has('projects')) {
            $projectIds = $requestOrInteger->input('projects', '');
        } elseif (is_int($requestOrInteger)) {
            $projectIds = $requestOrInteger;
        }

        // Process and normalize the project IDs
        return $this->processProjectIds($projectIds);
    }

    /**
     * Process and normalize project IDs.
     *
     * @param mixed $projectIds
     * @return array
     */
    private function processProjectIds($projectIds)
    {
        if (empty($projectIds)) {
            return [];
        }

        // Convert the input to an array of integers
        $projectIds = is_array($projectIds) ? $projectIds : $this->convertInputToIds($projectIds);

        return array_map('intval', $projectIds);
    }

    /**
     * Convert input to an array of project IDs.
     *
     * @param mixed $input
     * @return array
     */
    private function convertInputToIds($input)
    {
        $decodedProjects = json_decode($input, true);

        if (is_array($decodedProjects)) {
            return array_column($decodedProjects, 'id');
        }

        $projectIds = array_filter(explode(',', trim($input, ',')));

        return array_map('intval', $projectIds);
    }

    /**
     * Sync project assets with the given project IDs and asset model class.
     *
     * @param array $projectIds
     * @param string $assetModelClass
     * @return void
     */
    private function syncProjectAssets(array $projectIds, string $assetModelClass)
    {
        $this->projectAssets()->syncWithPivotValues($projectIds, ['asset_type' => $assetModelClass]);
    }

    public function getProjectsAttribute()
    {
        // Check if the project asset model class exists
        if (!class_exists(self::PROJECT_ASSET_MODEL_CLASS)) {
            return json_encode([]);
        }

        $projectAssets = self::PROJECT_ASSET_MODEL_CLASS::where('asset_id', $this->id)
            ->where('asset_type', get_class($this))
            ->get();

        return json_encode($projectAssets->map(function ($projectAsset) {
            return $projectAsset->project;
        }));
    }
}

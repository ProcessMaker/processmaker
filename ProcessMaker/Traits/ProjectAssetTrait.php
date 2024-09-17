<?php

namespace ProcessMaker\Traits;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Exception\ProjectAssetSyncException;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

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
            $this->updateProjectUpdatedAt($projectIds);
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
        } elseif (is_string($input)) {
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

    /**
     * Update the 'updated_at' field of projects.
     *
     * @param array|int $projectIds
     * @return void
     */
    public function updateProjectUpdatedAt($projectIds)
    {
        if (!class_exists(self::PROJECT_MODEL_CLASS)) {
            return;
        }

        foreach ((array) $projectIds as $projectId) {
            $project = self::PROJECT_MODEL_CLASS::find($projectId);

            if ($project) {
                $project->touch();
            }
        }
    }

    private static function clearAndRebuildUserProjectAssetsCache()
    {
        if (!hasPackage('package-projects')) {
            return;
        }
        if (Auth::user()) {
            $userId = Auth::user()->id;
            Cache::forget("user_{$userId}_project_assets");

            Cache::remember("user_{$userId}_project_assets", 86400, function () use ($userId) {
                return self::getUserProjectsAssets($userId);
            });
        }
    }

    private static function getProjectAssetsForUser($userId)
    {
        if (!hasPackage('package-projects')) {
            return [];
        }

        return Cache::remember("user_{$userId}_project_assets", 86400, function () use ($userId) {
            return self::getUserProjectsAssets($userId);
        });
    }

    private static function getUserProjectsAssets($userId)
    {
        $userProjectsWithAssets = self::fetchUserProjectAssets($userId);

        return self::formatProjectAssetsArray($userProjectsWithAssets);
    }

    /**
     * Fetch projects with assets for the given user.
     */
    private static function fetchUserProjectAssets($userId)
    {
        // Fetch projects where the user is the owner
        $ownerProjects = DB::table('projects')
            ->where('user_id', $userId)
            ->pluck('id')
            ->toArray();

        // Fetch projects where the user is a member (User::class) or belongs to a group (Group::class)
        $memberProjects = DB::table('project_members')
            ->where(function ($query) use ($userId) {
                $query->where('member_id', $userId)
                    ->where('member_type', User::class);
            })
            ->orWhere(function ($query) use ($userId) {
                $query->where('member_type', Group::class)
                    ->whereIn('member_id', function ($subQuery) use ($userId) {
                        $subQuery->select('group_id')
                            ->from('group_members')
                            ->where('member_id', $userId);
                    });
            })
            ->pluck('project_id')
            ->toArray();
        // Combine both sets of project IDs and remove duplicates
        $projectIds = array_unique(array_merge($ownerProjects, $memberProjects));

        // Fetch project assets for the combined project IDs
        return DB::table('projects')
        ->join('project_assets', 'projects.id', '=', 'project_assets.project_id')
        ->whereIn('projects.id', $projectIds)
        ->select('project_assets.asset_id as asset_id', 'project_assets.asset_type')
        ->get()
        ->unique()
        ->toArray();
    }

    /**
     * Format projects with assets into the desired structure.
     */
    private static function formatProjectAssetsArray($projectsWithAssets)
    {
        $formattedArray = [];

        foreach ($projectsWithAssets as $project) {
            $assetType = $project->asset_type;
            $assetId = $project->asset_id;

            if (!isset($formattedArray[$assetType])) {
                $formattedArray[$assetType] = [];
            }

            if (!isset($formattedArray[$assetType])) {
                $formattedArray[$assetType] = [];
            }

            $formattedArray[$assetType][] = $assetId;
        }

        return $formattedArray;
    }
}

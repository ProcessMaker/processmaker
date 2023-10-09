<?php

namespace ProcessMaker\Traits;

use Carbon\Carbon;

trait ProjectAssetTrait
{
    public function assignAssetsToProjects($request, $assetModelClass)
    {
        if ($request->input('projects')) {
            $projectAssetModelClass = 'ProcessMaker\Package\Projects\Models\ProjectAsset';
            $projectAssets = new $projectAssetModelClass;
            $projectIds = explode(',', $request->input('projects'));
            $assetData = [];

            foreach ($projectIds as $id) {
                $now = Carbon::now('utc')->toDateTimeString();
                $assetData[] = [
                    'asset_id' => $this->id,
                    'project_id' => $id,
                    'asset_type' => $assetModelClass,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $projectAssets::createMany($assetData);
        }
    }
}

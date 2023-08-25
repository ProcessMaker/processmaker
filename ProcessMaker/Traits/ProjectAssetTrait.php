<?php

namespace ProcessMaker\Traits;

trait ProjectAssetTrait
{
    public function assignAssetsToProjects($request, $assetModelClass)
    {
        if ($request->input('projects')) {
            $projectAssetModelClass = 'ProcessMaker\Package\Projects\Models\ProjectAsset';
            $projectAssets = new $projectAssetModelClass;
            $projectIds = (array) $request->input('projects');
            $assetData = [];

            foreach ($projectIds as $id) {
                $assetData[] = [
                    'asset_id' => $this->id,
                    'project_id' => $id,
                    'asset_type' => $assetModelClass,
                ];
            }

            $projectAssets::createMany($assetData);
        }
    }

    public function getAssignedAssetProjects($assetModelClass)
    {
        // dd($assetModelClass);
        // if ($request->input('projects')) {
        // $projectAssetModelClass = 'ProcessMaker\Package\Projects\Models\ProjectAsset';
        // $projectAssets = new $projectAssetModelClass;
        // $projectIds = (array) $request->input('projects');
        // $assetData = [];

        // foreach ($projectIds as $id) {
            //     $assetData[] = [
            //         'asset_id' => $this->id, // Assuming this trait is used in a model
            //         'project_id' => $id,
            //         'asset_type' => $assetModelClass,
            //     ];
        // }

        // $projectAssets::createMany($assetData);
        // }
    }
}

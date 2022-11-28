<?php

namespace ProcessMaker\ImportExport\Entities;

use ProcessMaker\ImportExport\Asset;
use ProcessMaker\ImportExport\AssetStore;

class ModelEntity extends Entity
{
    public $model;

    public $key = 'uuid'; // Determines if the asset already exists in the target instance

    public $mergeFunction = 'ask'; // Ask the user how they want to import the asset

    public function makeAsset($id) : Asset
    {
        $this->model = $this->params['class']::findOrFail($id);

        return app(Asset::class, [
            'entity' => $this,
            'id' => $id,
            'payload' => $this->model->toArray(),
        ]);
    }

    public function export($id)
    {
        if ($this->assetStore->hasByEntitiyAndId($this, $id)) {
            return $this->assetStore->getByEntitiyAndId($this, $id);
        }

        $asset = $this->makeAsset($id, []);

        $this->assetStore->add($asset);

        foreach ($this->getReferences() as $strategy) {
            $strategy->sourceAsset = $asset;
            $strategy->export();
        }

        return $asset;
    }
}

<?php

namespace ProcessMaker\ImportExport\Strategies;

use ProcessMaker\ImportExport\Contracts\Entity;
use ProcessMaker\ImportExport\Contracts\Strategy as StrategyInterface;
use ProcessMaker\ImportExport\AssetStore;
use ProcessMaker\ImportExport\Dependent;
use ProcessMaker\ImportExport\Asset;

class Strategy implements StrategyInterface
{
    public Asset $sourceAsset;

    public function __construct(
        private AssetStore $assetStore,
        public Entity $sourceEntity,
        public Entity $destinationEntity,
        public array $params,
    ) {
    }

    // protected function addAsset($id, array $meta = [])
    // {
    //     if (!$this->assetStore->has($this->referenceEntity, $id)) {
    //         $asset = $this->referenceEntity->makeAsset($id, $meta);
    //         $this->assetStore->add($asset);
    //     }
    // }
    
    protected function addDependent($id, $meta)
    {
        // $asset = $this->assetStore->get($this->referenceEntity, $entityId);
        // if (!$asset) {
            // $asset = $this->destinationEntity->makeAsset($id);
            // $this->assetStore->add($asset);
        // }

        $asset = $this->destinationEntity->export($id);
        $dependent = app()->make(Dependent::class, ['strategy' => $this, 'asset' => $asset, 'meta' => $meta]);
        $this->sourceAsset->addDependent($dependent);
    }
}

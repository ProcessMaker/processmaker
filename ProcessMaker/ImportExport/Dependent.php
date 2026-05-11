<?php

namespace ProcessMaker\ImportExport;

class Dependent
{
    public function __construct(
        public string $type,
        public string $uuid,
        public Manifest $manifest,
        public mixed $meta,
        public string $exporterClass,
        public string $modelClass,
        public array $fallbackMatches,
        public bool $discard = false
        ) {
    }

    public function toArray()
    {
        return [
            'type' => $this->type,
            'uuid' => $this->uuid,
            'meta' => $this->meta,
            'exporterClass' => $this->exporterClass,
            'modelClass' => $this->modelClass,
            'fallbackMatches' => $this->fallbackMatches,
            'name' => $this->name,
            'discard' => $this->discard,
        ];
    }

    public static function fromArray(array $array, Manifest $manifest)
    {
        return array_map(function ($dependent) use ($manifest) {
            return new self(
                $dependent['type'],
                $dependent['uuid'],
                $manifest,
                $dependent['meta'],
                $dependent['exporterClass'],
                $dependent['modelClass'],
                $dependent['fallbackMatches'],
            );
        }, $array);
    }

    public function __get(string $property)
    {
        $asset = $this->manifest->get($this->uuid);
        $value = null;

        if ($property === 'model' && !$asset) {
            $value = $this->getDiscardedModel();
        } elseif ($property === 'mode') {
            $value = $this->getMode($asset);
        } elseif ($property === 'name') {
            $value = $this->getName($asset);
        } elseif ($asset) {
            $value = $asset->$property;
        }

        return $value;
    }

    private function getDiscardedModel()
    {
        if ($this->canUseDiscardedDependentFinder()) {
            return $this->exporterClass::findDiscardedDependentModel($this);
        }

        return $this->findPersistedDiscardedModel();
    }

    private function canUseDiscardedDependentFinder(): bool
    {
        if (!method_exists($this->exporterClass, 'findDiscardedDependentModel')) {
            return false;
        }

        if (!method_exists($this->exporterClass, 'shouldFindDiscardedDependentModel')) {
            return true;
        }

        return $this->exporterClass::shouldFindDiscardedDependentModel($this);
    }

    private function findPersistedDiscardedModel()
    {
        // Attempt to reconstruct discarded model if it exists on the target instance
        $assetInfo = [
            'model' => $this->modelClass,
            'attributes' => $this->fallbackMatches,
        ];

        [, $model] = Manifest::getModel($this->uuid, $assetInfo, 'discard', $this->exporterClass, false);

        // Only return the model if it is persisted in the database
        return $model && $model->exists ? $model : null;
    }

    private function getMode(mixed $asset): string
    {
        return $asset ? $asset->mode : 'discard';
    }

    private function getName(mixed $asset): string
    {
        return $asset ? $asset->getName($this->model) : '';
    }
}

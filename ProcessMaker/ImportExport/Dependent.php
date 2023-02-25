<?php

namespace ProcessMaker\ImportExport;

class Dependent
{
    public function __construct(
        public string $type,
        public string $uuid,
        public Manifest $manifest,
        public $meta,
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

    public function __get($property)
    {
        $asset = $this->manifest->get($this->uuid);

        if ($property === 'model' && !$asset) {
            // Attempt to reconstruct discarded model if it exists on the target instance
            $assetInfo = [
                'model' => $this->modelClass,
                'attributes' => $this->fallbackMatches,
            ];

            list($_, $model) = Manifest::getModel($this->uuid, $assetInfo, 'discard', $this->exporterClass);

            // Only return the model if it is persisted in the database
            if ($model && $model->exists) {
                return $model;
            }
        }

        if ($property === 'mode') {
            if ($asset) {
                return $asset->mode;
            } else {
                return 'discard';
            }
        }

        if (!$asset) {
            return null;
        }

        return $asset->$property;
    }
}

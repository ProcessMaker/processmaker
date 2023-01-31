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
        public array $reAssociateUsing
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
            'reAssociateUsing' => $this->reAssociateUsing,
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
                $dependent['reAssociateUsing']
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
                'attributes' => $this->reAssociateUsing,
            ];

            list($mode, $model) = Manifest::getModel($this->uuid, $assetInfo, 'discard', $this->exporterClass);

            // Only return the model if it is persisted in the database
            if ($model && $model->exists) {
                return $model;
            }

            // TODO: Don't change the attribute on the target instance if the dependent model is not found on the target instance.
            // and unset it if it's new
        }

        if (!$asset) {
            return null;
        }

        return $asset->$property;
    }
}

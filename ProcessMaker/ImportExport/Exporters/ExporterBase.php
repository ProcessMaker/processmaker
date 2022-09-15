<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\ImportExport\Dependent;
use ProcessMaker\ImportExport\Manifest;

abstract class ExporterBase implements ExporterInterface
{
    public $model = null;

    public $dependents = [];

    public $manifest = null;

    public function __construct(Model $model, Manifest $manifest)
    {
        $this->model = $model;
        $this->manifest = $manifest;
    }

    public function uuid() : string
    {
        return $this->model->uuid;
    }

    public function addDependent(string $type, Model $dependentModel, string $exporterClass)
    {
        $uuid = $dependentModel->uuid;

        if (!$this->manifest->has($uuid)) {
            $exporter = new $exporterClass($dependentModel, $this->manifest);
            $this->manifest->push($uuid, $exporter);
            $exporter->export();
        }

        $this->dependents[] = new Dependent($type, $uuid, $this->manifest);
    }

    public function getExportAttributes()
    {
        $attrs = $this->model->getAttributes();
        unset($attrs['id']);

        return $attrs;
    }

    public function toArray()
    {
        return [
            'exporter' => get_class($this),
            'model' => get_class($this->model),
            'attributes' => $this->getExportAttributes(),
            'dependents' => array_map(fn ($d) => $d->toArray(), $this->dependents),
            'existing' => $this->model->exists ? $this->model->id : null,
        ];
    }
}

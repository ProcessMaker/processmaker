<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\ImportExport\Dependent;
use ProcessMaker\ImportExport\Manifest;

abstract class ExporterBase implements ExporterInterface
{
    public $model = null;

    public $dependents = [];

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

        $this->dependents[] = new Dependent($type, $uuid);
    }

    public function tree()
    {
        $rootDependent = new Dependent('root', $this->model->uuid);

        return $this->treeRecursion([$rootDependent]);
    }

    public function treeRecursion($dependents, $depth = 0)
    {
        $r = [];
        foreach ($dependents as $dependent) {
            $exporter = $this->manifest->get($dependent->uuid);
            if ($depth > 3) {
                $dependentsInfo = implode(',', array_map(fn ($d) => "($d->type) $d->uuid", $exporter->dependents));
            } else {
                $dependentsInfo = $this->treeRecursion($exporter->dependents, $depth + 1);
            }

            $r[] = [
                'type' => $dependent->type,
                'class'=> get_class($exporter),
                'uuid' => $dependent->uuid,
                'dependents' => $dependentsInfo,
            ];
        }

        return $r;
    }
}

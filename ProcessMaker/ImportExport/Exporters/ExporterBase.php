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

    public $importMode = null;

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
        // Mostly for debugging purposes
        $name = null;
        if (isset($this->model->name)) {
            $name = $this->model->name;
        } elseif (isset($this->model->title)) {
            $name = $this->model->title;
        }

        return [
            'exporter' => get_class($this),
            'name' => $name,
            'model' => get_class($this->model),
            'attributes' => $this->getExportAttributes(),
            'dependents' => array_map(fn ($d) => $d->toArray(), $this->dependents),
            'existing' => $this->model->exists ? $this->model->id : null,
        ];
    }

    public function updateDuplicateAttributes()
    {
        if ($this->importMode !== 'new') {
            return;
        }

        $class = get_class($this->model);

        foreach ($this->handleDuplicateAttributes() as $attribute => $handler) {
            $value = $this->model->$attribute;
            $i = 0;
            while ($this->duplicateExists($class, $attribute, $value)) {
                if ($i > 5) {
                    throw new \Exception('Can not fix duplicate attribute');
                }
                $i++;
                $value = $handler($value);
            }
            $this->model->$attribute = $value;
        }
    }

    private function duplicateExists($class, $attribute, $value)
    {
        return $class::where($attribute, $value)->exists();
    }

    public function handleDuplicateAttributes() : array
    {
        return [];
    }

    protected function incrementString(string $string)
    {
        return $string . ' new';
    }
}

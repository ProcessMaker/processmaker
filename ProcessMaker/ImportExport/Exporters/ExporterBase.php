<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\ImportExport\Dependent;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\Extension;
use ProcessMaker\ImportExport\Manifest;

abstract class ExporterBase implements ExporterInterface
{
    public $model = null;

    public $dependents = [];

    public $references = [];

    public $manifest = null;

    public $importMode = null;

    public $originalId = null;

    public function __construct(Model $model, Manifest $manifest)
    {
        $this->model = $model;
        $this->manifest = $manifest;
    }

    public function uuid() : string
    {
        return $this->model->uuid;
    }

    public function addDependent(string $type, Model $dependentModel, string $exporterClass, $meta = null)
    {
        $uuid = $dependentModel->uuid;

        if (!$this->manifest->has($uuid)) {
            $exporter = new $exporterClass($dependentModel, $this->manifest);
            $this->manifest->push($uuid, $exporter);
            $exporter->runExport();
        }
        $this->dependents[] = new Dependent($type, $uuid, $this->manifest, $meta);
    }

    public function getDependents($type)
    {
        return array_values(
            array_filter($this->dependents, fn ($d) => $d->type === $type)
        );
    }

    public function runExport()
    {
        $extensions = app()->make(Extension::class);
        $extensions->runExtensions($this, 'preExport');
        $this->export();
        $extensions->runExtensions($this, 'postExport');
    }

    public function runImport()
    {
        $extensions = app()->make(Extension::class);
        $extensions->runExtensions($this, 'preImport');
        $this->import();
        $extensions->runExtensions($this, 'postImport');
    }

    public function addReference($type, $attributes)
    {
        $this->references[$type] = $attributes;
    }

    public function getReference($type)
    {
        return $this->references[$type];
    }

    protected function getExportAttributes() : array
    {
        return $this->model->getAttributes();
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
            'references' => $this->references,
            'dependents' => array_map(fn ($d) => $d->toArray(), $this->dependents),
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
                if ($i > 100) {
                    throw new \Exception('Can not fix duplicate attribute after 100 iterations');
                }
                $i++;
                $value = $handler($value);
            }
            $this->model->$attribute = $value;
        }
    }

    private function duplicateExists($class, $attribute, $value) : bool
    {
        // Check the databse for duplicates unrelated to the import
        if ($class::where($attribute, $value)->exists()) {
            return true;
        }
        // Check the manifest to see if any non-saved models exist
        return $this->manifest->modelExists($class, $attribute, $value);
    }

    public function handleDuplicateAttributes() : array
    {
        return [];
    }

    protected function incrementString(string $string)
    {
        if (preg_match('/\s(\d+)$/', $string, $matches)) {
            $num = (int) $matches[1];
            $new = $num + 1;

            return preg_replace('/\d+$/', (string) $new, $string);
        }

        return $string . ' 2';
    }

    protected function exportCategories()
    {
        foreach ($this->model->categories as $category) {
            $this->addDependent(DependentType::CATEGORIES, $category, CategoryExporter::class);
        }
    }

    protected function associateCategories($categoryClass, $property)
    {
        $categories = $this->model->categories;
        foreach ($this->getDependents(DependentType::CATEGORIES) as $dependent) {
            $categories->push($categoryClass::findOrFail($dependent->model->id));
        }
        $this->model->$property = $categories->map(fn ($c) => $c->id)->join(',');
    }

    public static function registerExtension($class)
    {
        $exporterClass = static::class;
        app()->make(Extension::class)->register($exporterClass, $class);
    }
}

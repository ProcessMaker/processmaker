<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ProcessMaker\ImportExport\Dependent;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\Extension;
use ProcessMaker\ImportExport\Manifest;
use ProcessMaker\Traits\HasVersioning;

abstract class ExporterBase implements ExporterInterface
{
    public $model = null;

    public $dependents = [];

    public $references = [];

    public $manifest = null;

    public $importMode = null;

    public $originalId = null;

    public $disableEventsWhenImporting = false;

    public static function modelFinder($uuid, $asssetInfo)
    {
        return $asssetInfo['model']::where('uuid', $uuid);
    }

    public static function prepareAttributes($attrs)
    {
        unset($attrs['id']);

        return $attrs;
    }

    public function __construct(?Model $model, Manifest $manifest)
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
            array_filter($this->dependents, fn ($d) => $d->type === $type && !is_null($d->model))
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

    public function getName(): string
    {
        $name = 'unknown';
        if (isset($this->model->name)) {
            $name = $this->model->name;
        } elseif (isset($this->model->title)) {
            $name = $this->model->title;
        }

        return $name;
    }

    public function getType(): string
    {
        $basename = class_basename($this->model);

        return Str::snake("{$basename}_package");
    }

    public function toArray()
    {
        return [
            'exporter' => get_class($this),
            'name' => $this->getName(),
            'last_modified_by' => $this->getLastModifiedBy(),
            'process_manager' => $this->getProcessManager(),
            'model' => get_class($this->model),
            'attributes' => $this->getExportAttributes(),
            'references' => $this->references,
            'dependents' => array_map(fn ($d) => $d->toArray(), $this->dependents),
        ];
    }

    public function getProcessManager(): string
    {
        $managerName = 'Unknown';

        if (isset($this->model->manager->fullname)) {
            $managerName = $this->model->manager->fullname;
        }

        return $managerName;
    }

    public function getLastModifiedBy()
    {
        $versionHistoryClass = '\ProcessMaker\Package\Versions\Models\VersionHistory';

        if (!class_exists($versionHistoryClass)) {
            return '';
        }

        if (!in_array(HasVersioning::class, class_uses_recursive(get_class($this->model)))) {
            return '';
        }

        $version = $this->model->getLatestVersion();

        $versionHistory = $versionHistoryClass::where([
            'versionable_id' => $version->id,
            'versionable_type' => get_class($version),
        ])->first();

        if (!$versionHistory) {
            return '';
        }

        return $versionHistory->user->getFullName();
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
            if ($category->name == 'Uncategorized') {
                $this->addReference('uncategorized-category', true);
            } else {
                $this->addReference('uncategorized-category', false);
                $this->addDependent(DependentType::CATEGORIES, $category, CategoryExporter::class);
            }
        }
    }

    protected function associateCategories($categoryClass, $property)
    {
        $categories = $this->model->categories;
        foreach ($this->getDependents(DependentType::CATEGORIES) as $dependent) {
            $categories->push($categoryClass::findOrFail($dependent->model->id));
        }

        if ($categories->empty() && $this->getReference('uncategorized-category')) {
            $categories->push($categoryClass::where('name', 'Uncategorized')->firstOrFail());
        }

        $categoriesString = $categories->map(fn ($c) => $c->id)->unique()->join(',');
        if (!empty($categoriesString)) {
            $this->model->$property = $categoriesString;
        }
    }

    public static function registerExtension($class)
    {
        $exporterClass = static::class;
        app()->make(Extension::class)->register($exporterClass, $class);
    }
}

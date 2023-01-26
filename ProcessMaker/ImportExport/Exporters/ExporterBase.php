<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ProcessMaker\ImportExport\Dependent;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\Extension;
use ProcessMaker\ImportExport\Manifest;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\HasVersioning;

abstract class ExporterBase implements ExporterInterface
{
    public $model = null;

    public $dependents = [];

    public $references = [];

    public $manifest = null;

    public $importMode = null;

    public $originalId = null;

    public $forcePasswordProtect = false;

    public $importing = false;

    public $disableEventsWhenImporting = false;

    public $handleDuplicatesByIncrementing = [];

    public $log = [];

    public static function modelFinder($uuid, $asssetInfo)
    {
        return $asssetInfo['model']::where('uuid', $uuid);
    }

    public static function doNotImport($uuid, $asssetInfo)
    {
        return false;
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

    public function getDependents($type = null)
    {
        if (!$type) {
            return array_values(
                array_filter($this->dependents, fn ($d) => !is_null($d->model))
            );
        }

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
        return Arr::get($this->references, $type, null);
    }

    protected function getExportAttributes() : array
    {
        return $this->model->getAttributes();
    }

    public function getName($model) : string
    {
        if (!$model) {
            $model = $this->model;
        }

        $name = 'unknown';
        if (isset($model->name)) {
            $name = $model->name;
        } elseif (isset($model->title)) {
            $name = $model->title;
        }

        return $name;
    }

    public function getType(): string
    {
        $basename = class_basename($this->model);

        return Str::snake("{$basename}_package");
    }

    public function getClassName(): string
    {
        $modelClass = get_class($this->model);

        return class_basename($modelClass);
    }

    public function getTypeHuman($type)
    {
        return trim(ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $type)));
    }

    public function toArray()
    {
        $attributes = [
            'exporter' => get_class($this),
            'name' => $this->getName($this->model),
            'type' => $this->getClassName(),
            'type_human' => $this->getTypeHuman($this->getClassName()),
            'type_plural' => Str::plural($this->getClassName()),
            'type_human_plural' => Str::plural($this->getTypeHuman($this->getClassName())),
            'description' => $this->getDescription(),
            'last_modified_by' => $this->getLastModifiedBy()['lastModifiedByName'],
            'last_modified_by_id' => $this->getLastModifiedBy()['lastModifiedById'],
            'process_manager' => $this->getProcessManager()['managerName'],
            'process_manager_id' => $this->getProcessManager()['managerId'],
            'model' => get_class($this->model),
            'attributes' => $this->getExportAttributes(),
            'extraAttributes' => $this->getExtraAttributes($this->model),
            'references' => $this->references,
            'dependents' => array_map(fn ($d) => $d->toArray(), $this->dependents),
            'force_password_protect' => $this->forcePasswordProtect,
        ];

        if ($this->importing) {
            $this->addImportAttributes($attributes);
        }

        return $attributes;
    }

    public function addImportAttributes(&$attributes)
    {
        $existingAttributes = null;
        $existingName = null;
        if ($this->model->id) {
            $existingModel = $attributes['model']::find($this->model->id);
            $existingAttributes = $existingModel->getAttributes();
            $existingName = $this->getName($existingModel);
        }

        $attributes = array_merge($attributes, [
            'import_mode' => $this->importMode,
            'existing_id' => $this->model->id,
            'existing_attributes' => $existingAttributes,
            'existing_name' => $existingName,
        ]);
    }

    public function getDescription()
    {
        if (!Schema::hasColumn($this->model->getTable(), 'description')) {
            return null;
        }

        return $this->model->description;
    }

    public function getExtraAttributes($model): array
    {
        return [];
    }

    public function getProcessManager(): array
    {
        return [
            'managerId' => $this->model->manager?->id ? $this->model->manager->id : null,
            'managerName' => $this->model->manager?->fullname ? $this->model->manager->fullname : '',
        ];
    }

    public function getLastModifiedBy() : array
    {
        $lastModifiedBy = [
            'lastModifiedByName' => '',
            'lastModifiedById' => null,
        ];

        $versionHistoryClass = '\ProcessMaker\Package\Versions\Models\VersionHistory';

        if (!class_exists($versionHistoryClass)) {
            return $lastModifiedBy;
        }

        if (!in_array(HasVersioning::class, class_uses_recursive(get_class($this->model)))) {
            return $lastModifiedBy;
        }

        $version = $this->model->getLatestVersion();

        if (!$version) {
            return $lastModifiedBy;
        }

        $versionHistory = $versionHistoryClass::where([
            'versionable_id' => $version->id,
            'versionable_type' => get_class($version),
        ])->first();

        if (!$versionHistory) {
            return $lastModifiedBy;
        }

        $lastModifiedBy['lastModifiedByName'] = $versionHistory->user->getFullName();
        $lastModifiedBy['lastModifiedById'] = $versionHistory->user->id;

        return $lastModifiedBy;
    }

    public function updateDuplicateAttributes()
    {
        if ($this->importMode === 'discard') {
            return;
        }

        foreach ($this->handleDuplicateAttributes() as $attribute => $handler) {
            $value = $this->model->$attribute;
            $i = 0;
            while ($this->duplicateExists($attribute, $value)) {
                if ($i > 100) {
                    throw new \Exception('Can not fix duplicate attribute after 100 iterations');
                }
                $i++;
                $value = $handler($value);
            }
            $this->model->$attribute = $value;
        }
    }

    private function duplicateExists($attribute, $value) : bool
    {
        $class = get_class($this->model);

        // Check the database for duplicates unrelated to the import
        $query = $class::where($attribute, $value);

        // If this model is persisted, exclude it from the search
        if ($this->model->exists) {
            $query = $query->where('id', '!=', $this->model->id);
        }

        if ($query->exists()) {
            return true;
        }

        // Check the manifest to see if any non-saved models exist
        return $this->manifest->modelExists($class, $attribute, $value);
    }

    public function handleDuplicateAttributes() : array
    {
        $handlers = [];
        foreach ($this->handleDuplicatesByIncrementing as $attr) {
            $handlers[$attr] = fn ($name) => $this->incrementString($name);
        }

        return $handlers;
    }

    public function log($key, $value)
    {
        $this->log[$key] = $value;
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

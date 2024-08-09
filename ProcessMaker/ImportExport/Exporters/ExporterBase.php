<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ProcessMaker\ImportExport\Dependent;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\Extension;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\ImportExport\Manifest;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\ImportExport\Psudomodels\Psudomodel;
use ProcessMaker\ProcessTranslations\Languages;
use ProcessMaker\Traits\HasVersioning;

abstract class ExporterBase implements ExporterInterface
{
    // public $model = null;

    public $dependents = [];

    public $references = [];

    // public $manifest = null;

    public $mode = null;

    // public $options = null;

    public $originalId = null;

    public $forcePasswordProtect = false;

    public $importing = false;

    public $disableEventsWhenImporting = false;

    public $handleDuplicatesByIncrementing = [];

    public $log = [];

    public $hidden = false;

    public $discard = false;

    public static $forceUpdate = false;

    // public $ignoreExplicitDiscard = false;

    public static $fallbackMatchColumn = null;

    public $matchedBy = null;

    public $incrementStringSeparator = ' ';

    public $logger = null;

    public $saveAssetsMode = null;

    public static function modelFinder($uuid, $assetInfo)
    {
        $class = $assetInfo['model'];
        $column = 'uuid';
        $matchedBy = null;
        $baseQuery = $class::query();

        // If table does not exists for model, continue.
        if (!Schema::hasTable($baseQuery->getModel()->getTable())) {
            return [null, null];
        }

        // Check if the model has soft deletes
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($class))) {
            $baseQuery->withTrashed();
        }

        $query = clone $baseQuery;
        $model = $query->where($column, $uuid)->first();

        if (!$model) {
            foreach ((array) static::$fallbackMatchColumn as $column) {
                $value = Arr::get($assetInfo, 'attributes.' . $column);
                if (!$value) {
                    continue;
                }
                $query = clone $baseQuery;
                $model = $query->where($column, $value)->first();
                if ($model) {
                    $matchedBy = $column;
                    break;
                }
            }
        } else {
            $matchedBy = $column;
        }

        return [$model, $matchedBy];
    }

    public static function doNotImport($uuid, $assetInfo)
    {
        return false;
    }

    public static function prepareAttributes($attrs)
    {
        if (isset($attrs['id'])) {
            unset($attrs['id']);
        }

        return $attrs;
    }

    public function __construct(
        public Model|Psudomodel|null $model,
        public Manifest $manifest,
        public Options $options,
        public $ignoreExplicitDiscard
    ) {
        $this->mode = $options->get('mode', $this->model->uuid);
        $this->saveAssetsMode = $options->get('saveAssetsMode', $this->model->uuid);
        $this->logger = new Logger();
    }

    public function uuid() : string
    {
        return $this->model->uuid;
    }

    public function include() : bool
    {
        if ($this->discard) { // Explicit Discard (not from passed-in options)
            if (!$this->ignoreExplicitDiscard) {
                return false;
            }
        }

        if ($this->mode === 'discard') {  // passed in by options
            return false;
        }

        return true;
    }

    public function addDependent(string $type, Model|Psudomodel $dependentModel, string $exporterClass, $meta = null)
    {
        $uuid = $dependentModel->uuid;
        if (!$this->manifest->has($uuid)) {
            $exporter = new $exporterClass($dependentModel, $this->manifest, $this->options, $this->ignoreExplicitDiscard);
            if ($exporter->include()) {
                $this->manifest->push($uuid, $exporter);
                $exporter->runExport();
            }
        }

        $fallbackMatches = [];
        foreach ((array) $exporterClass::$fallbackMatchColumn as $column) {
            $fallbackMatches[$column] = $dependentModel->$column;
        }

        $dependent = new Dependent(
            $type,
            $uuid,
            $this->manifest,
            $meta,
            $exporterClass,
            get_class($dependentModel),
            $fallbackMatches
        );
        $this->dependents[] = $dependent;

        return $dependent;
    }

    public function getDependents($type = null, $includeEmptyModels = false)
    {
        return array_values(
            array_filter($this->dependents, function ($dependent) use ($type, $includeEmptyModels) {
                if ($type && $dependent->type !== $type) {
                    return false;
                }
                if ($dependent->model === null) {
                    if ($includeEmptyModels) {
                        // Return an empty model when the dependent does not exist
                        $dependent->model = new $dependent->modelClass();

                        return true;
                    }

                    return false;
                }

                return true;
            })
        );
    }

    public function runExport()
    {
        try {
            $extensions = app()->make(Extension::class);
            $extensions->runExtensions($this, 'preExport', $this->logger);
            $this->export();
            $extensions->runExtensions($this, 'postExport', $this->logger);
        } catch (ModelNotFoundException $e) {
            \Log::error($e->getMessage());
        }
    }

    public function runImport($existingAssetInDatabase = null, $importingFromTemplate = false)
    {
        // Ensure we have the latest version of the model because some attributes
        // may have changed with updateDuplicateAttributes()
        $this->model->refresh();

        $extensions = app()->make(Extension::class);
        $extensions->runExtensions($this, 'preImport', $this->logger);
        $this->logger->log('Running Import ' . static::class);
        $this->import($existingAssetInDatabase, $importingFromTemplate);
        $extensions->runExtensions($this, 'postImport', $this->logger);
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
        } elseif ($this->getClassName() === 'LaunchpadSetting') {
            $name = 'Setting';
        }

        return $name;
    }

    public function getExportType(): string
    {
        $basename = class_basename($this->model);

        return Str::snake("{$basename}_package");
    }

    public function getClassName(): string
    {
        $modelClass = get_class($this->model);
        $baseName = class_basename($modelClass);

        // Map the aliases for base class names.
        $aliases = [
            'ProcessLaunchpad' => 'LaunchpadSetting',
        ];

        if (array_key_exists($baseName, $aliases)) {
            $baseName = $aliases[$baseName];
        }

        return $baseName;
    }

    public function getTypeHuman($type)
    {
        return trim(ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $type)));
    }

    public function toArray()
    {
        $attributes = [
            'exporter' => get_class($this),
            'type' => $this->getClassName(),
            'type_human' => Str::singular($this->getTypeHuman($this->getClassName())),
            'type_plural' => Str::plural($this->getClassName()),
            'type_human_plural' => Str::plural($this->getTypeHuman($this->getClassName())),
            'last_modified_by' => $this->getLastModifiedBy()['lastModifiedByName'],
            'last_modified_by_id' => $this->getLastModifiedBy()['lastModifiedById'],
            'model' => get_class($this->model),
            'force_password_protect' => $this->forcePasswordProtect,
            'hidden' => $this->hidden,
            'mode' => $this->mode,
            'saveAssetsMode' => $this->saveAssetsMode,
            'explicit_discard' => $this->discard,
            'dependents' => array_map(fn ($d) => $d->toArray(), $this->dependents),
            'name' => $this->getName($this->model),
            'description' => $this->getDescription(),
            'process_manager' => $this->getProcessManager()['managerName'],
            'process_manager_id' => $this->getProcessManager()['managerId'],
            'attributes' => $this->getExportAttributes(),
            'extraAttributes' => $this->getExtraAttributes($this->model),
            'references' => $this->references,
        ];

        if ($this->importing) {
            $this->addImportAttributes($attributes);
        }

        return $attributes;
    }

    public function addImportAttributes(&$attributes)
    {
        $existingId = null;
        $existingAttributes = null;
        $existingName = null;
        if ($this->model->exists) {
            $existingAttributes = $this->model->getOriginal();
            $class = get_class($this->model);
            $model = new $class($existingAttributes);
            $existingName = $this->getName($model);
            $existingId = $existingAttributes['id'];
        }

        $attributes = array_merge($attributes, [
            'existing_id' => $existingId,
            'existing_attributes' => $existingAttributes,
            'existing_name' => $existingName,
            'matched_by' => $this->matchedBy,
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
        $translatedLanguages = [];
        if ($model->translations) {
            foreach ($model->translations as $key => $value) {
                $translatedLanguages[$key] = Languages::ALL[$key];
            }
        }

        return [
            'translatedLanguages' => $translatedLanguages,
        ];
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
        if ($this->mode === 'discard') {
            return;
        }
        foreach ($this->handleDuplicateAttributes() as $attribute => $handler) {
            $value = $this->model->$attribute;
            $i = 0;
            while ($this->duplicateExists($attribute, $value)) {
                if ($i > 1000) {
                    throw new \Exception('Can not fix duplicate attribute after 1000 iterations');
                }
                $i++;
                $value = $handler($value);
                if ($value === null) {
                    break;
                }
            }
            $this->model->$attribute = $value;
        }
    }

    protected function duplicateExists($attribute, $value) : bool
    {
        $class = get_class($this->model);

        // Check the database for duplicates unrelated to the import
        $query = $class::where($attribute, $value);

        // Check the database for deleted duplicates related to the import
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($class))) {
            $query->withTrashed();
        }

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
        foreach ($this->handleDuplicatesByIncrementing as  $index => $attr) {
            if (is_array($this->incrementStringSeparator)) {
                $seperator = $this->incrementStringSeparator[$index];
                $handlers[$attr] = fn ($name) => $this->incrementString($name, $seperator);
            } elseif (is_null($this->incrementStringSeparator)) {
                $handlers[$attr] = fn ($value) => $this->incrementNumber($value);
            } else {
                $seperator = $this->incrementStringSeparator;
                $handlers[$attr] = fn ($name) => $this->incrementString($name, $seperator);
            }
        }

        return $handlers;
    }

    public function log($key, $value)
    {
        $this->log[$key] = $value;
    }

    protected function incrementString(string $string, $seperator = ' ')
    {
        if (preg_match('/' . $seperator . '(\d+)$/', $string, $matches)) {
            $num = (int) $matches[1];
            $new = $num + 1;

            return preg_replace(
                '/' . $seperator . '\d+$/',
                $seperator . (string) $new,
                $string
            );
        }

        return $string . $seperator . '2';
    }

    protected function incrementNumber(int $value): int
    {
        return $value + 1;
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
        $categories = collect([]);
        foreach ($this->getDependents(DependentType::CATEGORIES) as $dependent) {
            $categories->push($categoryClass::findOrFail($dependent->model->id));
        }
        // Get the options from the object, filter them for the `isTemplate` flag and get the first value.
        $manifest = $this->manifest->toArray(true);
        $options = (array) $this->options;
        $isTemplate = collect($options['options'])
            ->filter(function ($optionValue, $optionKey) use ($manifest) {
                return Arr::has($manifest, $optionKey);
            })
            ->map(function ($optionValue, $optionKey) {
                return Arr::get($optionValue, 'isTemplate');
            })
            ->first();

        // If the process has a set category, we need to verify the "Uncategorized"
        // category exists for this model and if it doesn't, recreate it
        $uncategorizedCategory = function () use ($categoryClass) {
            return $categoryClass::where('name', 'Uncategorized')->firstOrCreate([
                'name' => __('Uncategorized'),
                'status' => 'ACTIVE',
                'is_system' => false,
            ]);
        };

        // If a template is being used and an associated category is present, add that category to the collection.
        // Otherwise, if the collection is empty and there's an uncategorized reference, add the uncategorized category.
        if ($isTemplate && isset($this->model->process_category_id)) {
            if ($this->getReference('uncategorized-category')) {
                $categories->push($uncategorizedCategory());
            } else {
                $foundCategory = $categoryClass::find($this->model->process_category_id);

                if (!$foundCategory instanceof $categoryClass) {
                    \Log::warning('Import/Export: Unable to find ' . $categoryClass .
                        " with id {$this->model->process_category_id}, updated to 'Uncategorized'.");
                }

                $categories->push($foundCategory ?? $uncategorizedCategory());
            }
        } elseif ($categories->empty() && $this->getReference('uncategorized-category')) {
            $categories->push($uncategorizedCategory());
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

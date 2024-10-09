<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\ImportExport\Exporters\ExporterInterface;
use ReflectionClass;

class Manifest
{
    private $manifest = [];

    private $afterExportCallbacks = [];

    private $afterImportCallbacks = [];

    public static $parents = null;

    private static $tableColumns = [];

    private static $logger = null;

    public function has(string $uuid)
    {
        return array_key_exists($uuid, $this->manifest);
    }

    public function get($uuid)
    {
        return Arr::get($this->manifest, $uuid, null);
    }

    public function set($manifest)
    {
        return $this->manifest = $manifest;
    }

    public function all()
    {
        return $this->manifest;
    }

    public function afterExport($callback)
    {
        $this->afterExportCallbacks[] = $callback;
    }

    public function afterImport($callback)
    {
        $this->afterImportCallbacks[] = $callback;
    }

    public function runAfterExport()
    {
        foreach ($this->afterExportCallbacks as $callback) {
            $callback();
        }
    }

    public function runAfterImport()
    {
        foreach ($this->afterImportCallbacks as $callback) {
            $callback();
        }
    }

    public function toArray($skipHidden = false)
    {
        $manifest = [];
        foreach ($this->manifest as $uuid => $exporter) {
            if ($exporter->hidden && $skipHidden) {
                continue;
            }
            if ($exporter->mode === 'discard') {
                continue;
            }
            $manifest[$uuid] = $exporter->toArray();
        }

        return $manifest;
    }

    public static function fromArray(array $array, Options $options, $logger)
    {
        self::$logger = $logger;

        self::buildParentModeMap($array, $options);

        $manifest = new self();
        foreach ($array as $uuid => $assetInfo) {
            if (!self::checkClasses($assetInfo)) {
                continue;
            }
            $exporterClass = $assetInfo['exporter'];
            $modeOption = $options->get('mode', $uuid);
            $saveAssetsModeOption = $options->get('saveAssetsMode', $uuid);
            list($mode, $model, $matchedBy) = self::getModel($uuid, $assetInfo, $modeOption, $exporterClass);
            $model = self::updateBPMNDefinitions($model, $saveAssetsModeOption);
            $exporter = new $exporterClass($model, $manifest, $options, false);
            $exporter->logger = $logger;
            $exporter->importing = true;
            $exporter->mode = $mode;
            $exporter->matchedBy = $matchedBy;
            $exporter->originalId = Arr::get($assetInfo, 'attributes.id');
            $exporter->updateDuplicateAttributes();
            $exporter->dependents = Dependent::fromArray($assetInfo['dependents'], $manifest);
            $exporter->references = $assetInfo['references'];
            $manifest->push($uuid, $exporter);
        }

        return $manifest;
    }

    public static function checkClasses($assetInfo)
    {
        $modelClass = $assetInfo['model'];
        $exporterClass = $assetInfo['exporter'];

        foreach ([$modelClass, $exporterClass] as $class) {
            if (!class_exists($class)) {
                self::$logger?->warn("Class {$class} does not exist.");
                self::$logger?->addWarning("Class {$class} does not exist.");

                return false;
            }
        }

        return true;
    }

    public function push(string $uuid, ExporterInterface $exporter)
    {
        $this->manifest[$uuid] = $exporter;
    }

    public static function getModel($uuid, $assetInfo, $mode, $exporterClass)
    {
        $model = null;
        $class = $assetInfo['model'];
        $attrs = $assetInfo['attributes'];

        if ($mode === 'new') {
            throw new \Exception('Mode "new" can only be set by the system.');
        }

        [$model, $matchedBy] = $exporterClass::modelFinder($uuid, $assetInfo);

        if (self::isHidden($exporterClass)) {
            $parentMode = self::parentMode($uuid);
            if ($parentMode) {
                $mode = $parentMode;
            }
        }

        if ($exporterClass::doNotImport($uuid, $assetInfo)) {
            $mode = 'discard';
        }

        if ($exporterClass::$forceUpdate) {
            $mode = 'update';
        }

        $attrs = $exporterClass::prepareAttributes($attrs);

        if (!$model) {
            $model = new $class();
            if ($mode !== 'discard') {
                $mode = 'new';
            }
        }

        if (method_exists($model, 'getTable')) {
            $attrs = self::removeAttributesThatDontExist($attrs, $model->getTable());
        }

        $class::unguard();
        switch ($mode) {
            case 'update':
                $model->fill($attrs);
                break;
            case 'discard':
                // Keep the model, just don't save it later in doImport
                $model->preventSavingDiscardedModel();
                break;
            case 'copy':
                // Make new copy of the model with a new UUID
                unset($attrs['uuid']);
                // parameter for templates
                if (array_key_exists('editing_process_uuid', $attrs)) {
                    unset($attrs['editing_process_uuid']);
                }
                $model = new $class();
                $model->fill($attrs);
                break;
            case 'new':
                // NOT user settable
                // Create the model with the same UUID if it doesn't exist on the target instance
                $model->fill($attrs);
                $model->uuid = $uuid;
                break;
            default:
                throw new \Exception('Invalid mode: ' + $mode);
        }

        if ($model) {
            self::handleCasts($model);
        }

        $class::reguard();

        return [$mode, $model, $matchedBy];
    }

    private static function removeAttributesThatDontExist($attrs, $table)
    {
        $columns = self::getColumnsForTable($table);
        foreach ($attrs as $key => $value) {
            if (!in_array($key, $columns)) {
                unset($attrs[$key]);
                self::$logger?->warn("Attribute '$key' does not exist in the table '$table'");
                self::$logger?->addWarning("Attribute '$key' does not exist in the table '$table'");
            }
        }

        return $attrs;
    }

    private static function getColumnsForTable($table)
    {
        if (isset(self::$tableColumns[$table])) {
            return self::$tableColumns[$table];
        }
        $columns = Schema::getColumnListing($table);
        self::$tableColumns[$table] = $columns;

        return $columns;
    }

    private static function handleCasts(&$model)
    {
        foreach ($model->getCasts() as $field => $cast) {
            switch ($cast) {
                case 'array':
                    if (!is_array($model->$field)) {
                        $model->$field = json_decode($model->$field, true);
                    }
                    break;
                case 'object':
                    if (!is_object($model->$field)) {
                        if (gettype($model->$field) !== 'array') {
                            $model->$field = json_decode($model->$field);
                        } else {
                            $model->$field = (object) $model->$field;
                        }
                    }
                    break;
            }
        }
    }

    public function modelExists($class, $attribute, $value) : bool
    {
        foreach ($this->manifest as $uuid => $exporter) {
            if (get_class($exporter->model) === $class) {
                if ($exporter->model->$attribute === $value) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isHidden($exporterClass)
    {
        $reflection = new ReflectionClass($exporterClass);
        $props = $reflection->getDefaultProperties();
        if (isset($props['hidden']) && $props['hidden']) {
            return true;
        }

        return false;
    }

    public static function buildParentModeMap(array $import, Options $options)
    {
        self::$parents = [];
        foreach ($import as $parentUuid => $assetInfo) {
            foreach ($assetInfo['dependents'] as $dependent) {
                $dependentUuid = $dependent['uuid'];

                if (!isset(self::$parents[$parentUuid])) {
                    self::$parents[$parentUuid] = [];
                }

                $mode = $options->get('mode', $parentUuid);
                self::$parents[$dependentUuid][$parentUuid] = $mode;
            }
        }
    }

    public static function parentMode(string $uuid)
    {
        if (self::$parents === null) {
            throw new \Exception('Parents mode map has not been built yet.');
        }

        $parentMode = null;
        if (isset(self::$parents[$uuid])) {
            foreach (self::$parents[$uuid] as $parentUuid => $mode) {
                if ($mode) {
                    $parentMode = $mode;
                    break;
                }
            }
        }

        return $parentMode;
    }

    public static function updateBPMNDefinitions($model, $saveAssetsModeOption)
    {
        if ($saveAssetsModeOption === 'saveModelOnly') {
            $bpmn = $model->bpmn;
            $bpmn_definitions = simplexml_load_string($bpmn);
            // Find all instances of screenRef and scriptRef and set their values to empty strings
            foreach ($bpmn_definitions->xpath('//@pm:screenRef | //@pm:scriptRef') as $attribute) {
                $attribute[0] = '';
            }
            $new_bpmn_string = $bpmn_definitions->asXML();
            $model->bpmn = $new_bpmn_string;
        }

        return $model;
    }
}

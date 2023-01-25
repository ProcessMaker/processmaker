<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporters\ExporterInterface;
use stdClass;

class Manifest
{
    private $manifest = [];

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

    public function toArray()
    {
        $manifest = [];
        foreach ($this->manifest as $uuid => $exporter) {
            $manifest[$uuid] = $exporter->toArray();
        }

        return $manifest;
    }

    public static function fromArray(array $array, Options $options)
    {
        $manifest = new self();
        foreach ($array as $uuid => $assetInfo) {
            $exporterClass = $assetInfo['exporter'];
            list($importMode, $model) = self::getModel($uuid, $assetInfo, $options, $exporterClass);
            $exporter = new $exporterClass($model, $manifest);
            $exporter->importing = true;
            $exporter->importMode = $importMode;
            $exporter->originalId = Arr::get($assetInfo, 'attributes.id');
            $exporter->updateDuplicateAttributes();
            $exporter->dependents = Dependent::fromArray($assetInfo['dependents'], $manifest);
            $exporter->references = $assetInfo['references'];
            $manifest->push($uuid, $exporter);
        }

        return $manifest;
    }

    public function push(string $uuid, ExporterInterface $exporter)
    {
        $this->manifest[$uuid] = $exporter;
    }

    public static function getModel($uuid, $assetInfo, $options, $exporterClass)
    {
        $model = null;
        $class = $assetInfo['model'];
        $mode = $options->get('mode', $uuid);
        $attrs = $assetInfo['attributes'];

        $modelQuery = $exporterClass::modelFinder($uuid, $assetInfo);

        if ($exporterClass::doNotImport($uuid, $assetInfo)) {
            $mode = 'discard';
        }

        $attrs = $exporterClass::prepareAttributes($attrs);

        // Check if the model has soft deletes
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($class))) {
            $modelQuery->withTrashed();
        }

        if ($modelQuery->exists()) {
            $model = $modelQuery->firstOrFail();
        } else {
            if ($mode !== 'discard') {
                $mode = 'new';
            }
        }

        $class::unguard();
        switch ($mode) {
            case 'update':
                $model->fill($attrs);
                break;
            case 'discard':
                // Keep the model, just don't save it later in doImport
                $model = new $class();
                break;
            case 'copy':
                $model = new $class();
                unset($attrs['uuid']);
                $model->fill($attrs);
                break;
            case 'new':
                $model = new $class();
                $model->fill($attrs);
                $model->uuid = $uuid;
                break;
        }

        // dump(get_class($model), $mode);

        if ($model) {
            self::handleCasts($model);
        }

        $class::reguard();

        return [$mode, $model];
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
                        $model->$field = json_decode($model->$field);
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
}

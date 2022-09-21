<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use MJS\TopSort\Implementations\StringSort;
use ProcessMaker\ImportExport\Exporters\ExporterInterface;

class Manifest
{
    private $manifest = [];

    public function has(string $uuid)
    {
        return array_key_exists($uuid, $this->manifest);
    }

    public function get($uuid)
    {
        return $this->manifest[$uuid];
    }

    public function set($manifest)
    {
        return $this->manifest = $manifest;
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
            list($importMode, $model) = self::getModel($uuid, $assetInfo, $options);
            $exporter = new $assetInfo['exporter']($model, $manifest);
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

    public static function getModel($uuid, $assetInfo, $options)
    {
        $model = null;
        $class = $assetInfo['model'];
        $mode = $options->get('mode', $uuid);
        $attrs = $assetInfo['attributes'];
        unset($attrs['id']);

        $modelQuery = $class::where('uuid', $uuid);

        if ($modelQuery->exists()) {
            $model = $modelQuery->firstOrFail();
        } else {
            $mode = 'new';
        }

        switch ($mode) {
            case 'update':
                $model->fill($attrs);
                break;
            case 'discard':
                break;
            case 'new':
                $model = new $class();
                $model->fill($attrs);
                $model->uuid = $uuid;
                break;
        }

        return [$mode, $model];
    }

    public function orderForImport()
    {
        $sorter = new StringSort();
        foreach ($this->manifest as $uuid => $exporter) {
            $dependentUuids = array_map(fn ($d) => $d->uuid, $exporter->dependents);
            $sorter->add($uuid, $dependentUuids);
        }

        return $sorter->sort();
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

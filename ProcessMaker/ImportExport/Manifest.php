<?php

namespace ProcessMaker\ImportExport;

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
            $model = self::getModel($uuid, $assetInfo, $options);
            $exporter = new $assetInfo['exporter']($model, $manifest);
            $exporter->dependents = Dependent::fromArray($assetInfo['dependents'], $manifest);
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
        $class = $assetInfo['model'];
        $mode = $options->get('mode', $uuid);

        $modelQuery = $class::where('uuid', $uuid);

        if ($modelQuery->exists()) {
            $model = $modelQuery->first();
        } else {
            $mode = 'new';
        }

        switch ($mode) {
            case 'update':
                $model = $modelQuery->first();
                $model->fill($assetInfo['attributes']);

                return $model;
            case 'discard':
                return $modelQuery->first();
            case 'new':
                $model = new $class();
                $model->fill($assetInfo['attributes']);
                $model->uuid = $uuid;

                return $model;
        }
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
}

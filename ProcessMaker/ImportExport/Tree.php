<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\ImportExport\Exporters\ExporterInterface;

class Tree {
    const MAX_DEPTH = 10;

    private $manifest;

    public function __construct(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function tree(ExporterInterface $rootExporter)
    {
        $rootDependent = new Dependent('root', $rootExporter->uuid());
        return $this->treeRecursion([$rootDependent]);
    }

    private function treeRecursion($dependents, $depth = 0)
    {
        $r = [];
        foreach ($dependents as $dependent) {
            $exporter = $this->manifest->get($dependent->uuid);
            if ($depth > self::MAX_DEPTH) {
                // $dependentsInfo = implode(',', array_map(fn ($d) => "($d->type) $d->uuid", $exporter->dependents));
                throw new \Exception("Max depth exceeded. Do you have a circular reference?");
            } else {
                $dependentsInfo = $this->treeRecursion($exporter->dependents, $depth + 1);
            }

            $exporterArray = $exporter->toArray();
            unset($exporterArray['attributes']);
            unset($exporterArray['dependents']);

            $r[] = array_merge(
                $exporterArray,
                [
                    'type' => $dependent->type,
                    'uuid' => $dependent->uuid,
                    'dependents' => $dependentsInfo,
                ],
            );
        }

        return $r;
    }
}
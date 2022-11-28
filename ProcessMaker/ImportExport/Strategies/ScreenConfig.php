<?php

namespace ProcessMaker\ImportExport\Strategies;

use Illuminate\Support\Arr;

class ScreenConfig extends Strategy
{
    public function export()
    {
        $config = $this->sourceEntity->model->config;
        foreach($this->findNestedScreens($config) as $result) {
            $this->addDependent($result['id'], ['path' => $result['path']]);
        }
    }

    public function import()
    {
    }

    public function associate()
    {
        $screen = $this->sourceEntity->model;
        $config = $screen->config;

        foreach ($this->getDependents() as $dependent) {
            $screenId = $dependent->asset->model->id;
            Arr::set($config, $dependent->meta['path'], $screenId);
        }

        $screen->config = $config;
        $screen->saveOrFail();
    }

    private function findNestedScreens($config)
    {
        $results = [];
        foreach (Arr::dot($config) as $key => $value) {
            if ($value === 'FormNestedScreen' && str_ends_with($key, '.component')) {
                $parts = array_slice(explode('.', $key), 0, -1);
                $idPath = join(".", $parts) . '.config.screen';
                $id = Arr::get($config, $idPath);
                $results[] = ['path' => $idPath, 'id' => $id];
            }
        }
        return $results;
    }
}

<?php

namespace ProcessMaker\Assets;

use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\Screen;
use Illuminate\Support\Arr;

class DataSourcesInScreen
{
    public $type = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSource';

    public $owner = Screen::class;

    /**
     * Get the data sources (ex. watchers) used in a screen
     *
     * @param Screen $screen
     * @param array $dataSources
     *
     * @return array
     */
    public function referencesToExport(Screen $screen, array $dataSources = [])
    {
        $watchers = $screen->watchers;
        if (is_array($watchers)) {
            $this->findInArray($watchers, function ($item) use (&$dataSources) {
                if (is_array($item)) {
                    $scriptId = (string) ($item['script_id'] ?? '');
                    $id = (string) ($item['script']['id'] ?? '');
                    if (str_starts_with($scriptId, 'data_source-') || str_starts_with($id, 'data_source-')) {
                        $numericId = str_replace('data_source-', '', str_starts_with($scriptId, 'data_source-') ? $scriptId : $id);
                        if (is_numeric($numericId)) {
                            $dataSources[] = [$this->type, (int) $numericId];
                        }
                    }
                }
            });
        }

        $config = $screen->versionFor(null)->config;
        if (is_array($config)) {
            $this->findInArray($config, function ($item) use (&$dataSources) {
                if (is_array($item) && isset($item['component']) && $item['component'] === 'FormSelectList' && !empty($item['config']['options']['selectedDataSource'])) {
                    $dataSources[] = [$this->type, (int) $item['config']['options']['selectedDataSource']];
                }
            });
        }

        return $dataSources;
    }

    /**
     * Update references used in an imported screen
     *
     * @param Screen $screen
     * @param array $references
     * @param ExportManager $exportManager
     *
     * @return void
     */
    public function updateReferences(Screen $screen, array $references, ExportManager $exportManager)
    {
        $watches = $screen->watchers;
        if (is_array($watches)) {
            foreach ($watches as &$watcher) {
                $id = (string) ($watcher['script']['id'] ?? '');
                if (str_starts_with($id, 'data_source-')) {
                    $oldRef = str_replace('data_source-', '', $id);
                    if (isset($references[$this->type][$oldRef])) {
                        $newRef = $references[$this->type][$oldRef]->getKey();
                    } else {
                        $newRef = null;
                        $exportManager->addLogMessage(
                            'DataSourcesInScreen:references',
                            __(
                                'Imported file does not contain the data source #:dataSource assigned to a watcher',
                                ['dataSource' => $oldRef]
                            ),
                            false,
                            __("Missing watcher's data source")
                        );
                    }
                    if ($newRef) {
                        $watcher['script_id'] = $newRef;
                        $watcher['script']['id'] = "data_source-$newRef";
                        $watcher['script']['title'] = $references[$this->type][$oldRef]->name;
                    }
                }
            }
        }
        $screen->watchers = $watches;

        $config = $screen->config;
        if (is_array($config)) {
            $this->findInArray($config, function ($item, $keyDotNotation) use ($references, &$config) {
                if (is_array($item) && isset($item['component']) && $item['component'] === 'FormSelectList' && !empty($item['config']['options']['selectedDataSource'])) {
                    $oldRef = $item['config']['options']['selectedDataSource'];
                    if (isset($references[$this->type][$oldRef])) {
                        $newRef = $references[$this->type][$oldRef]->getKey();
                        Arr::set($config, "$keyDotNotation.config.options.selectedDataSource", $newRef);
                    }
                }
            });
            $screen->config = $config;
        }

        $screen->save();
    }

    /**
     * Find recursively in an array
     *
     * @param array $array
     * @param callable $callback
     * @param array $path
     *
     * @return void
     */
    private function findInArray(array $array, callable $callback, array $path = [])
    {
        call_user_func($callback, $array, implode('.', $path));
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $this->findInArray($item, $callback, array_merge($path, [$key]));
            } else {
                call_user_func($callback, $item, implode('.', array_merge($path, [$key])));
            }
        }
    }
}

<?php

namespace ProcessMaker\Http\Resources\V1_1;

use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\ScreenVersion as ScreenVersionResource;
use ProcessMaker\ProcessTranslations\ProcessTranslation;

class TaskScreen extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $array = $this->includeScreen($request);

        return $array;
    }

    private function includeScreen($request)
    {
        $array = ['screen' => null];

        $screen = $this->getScreenVersion();

        if ($screen) {
            if ($screen->type === 'ADVANCED') {
                $array['screen'] = $screen;
            } else {
                $resource = new ScreenVersionResource($screen);
                $array['screen'] = $resource->toArray($request);
            }
        } else {
            $array['screen'] = null;
        }

        if ($array['screen']) {
            // Apply translations to screen
            $processTranslation = new ProcessTranslation($this->process);
            $array['screen']['config'] = $processTranslation->applyTranslations($array['screen']);
            $array['screen']['config'] = $this->removeInspectorMetadata($array['screen']['config']);

            // Apply translations to nested screens
            if (array_key_exists('nested', $array['screen'])) {
                foreach ($array['screen']['nested'] as &$nestedScreen) {
                    $nestedScreen['config'] = $processTranslation->applyTranslations($nestedScreen);
                    $nestedScreen['config'] = $this->removeInspectorMetadata($nestedScreen['config']);
                }
            }
        }

        return $array;
    }

    /**
     * Removes the inspector metadata from the screen configuration
     *
     * @param array $config
     * @return array
     */
    private function removeInspectorMetadata(array $config)
    {
        foreach($config as $i => $page) {
            $config[$i]['items'] = $this->removeInspectorMetadataItems($page['items']);
        }
        return $config;
    }

    /**
     * Removes the inspector metadata from the screen configuration items
     *
     * @param array $items
     * @return array
     */
    private function removeInspectorMetadataItems(array $items)
    {
        foreach($items as $i => $item) {
            if (isset($item['inspector'])) {
                unset($item['inspector']);
            }
            if (isset($item['component']) && $item['component'] === 'FormMultiColumn') {
                foreach($item['items'] as $c => $col) {
                    $item['items'][$c] = $this->removeInspectorMetadataItems($col);
                }
            } elseif (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->removeInspectorMetadataItems($item['items']);
            }
            $items[$i] = $item;
        }
        return $items;
    }
}

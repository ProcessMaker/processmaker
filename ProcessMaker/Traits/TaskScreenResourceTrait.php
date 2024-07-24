<?php

namespace ProcessMaker\Traits;

trait TaskScreenResourceTrait
{

    /**
     * Removes the inspector metadata from the screen configuration
     *
     * @param array $config
     * @return array
     */
    private function removeInspectorMetadata(array $config)
    {
        foreach ($config as $i => $page) {
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
        foreach ($items as $i => $item) {
            if (isset($item['inspector'])) {
                unset($item['inspector']);
            }
            if (isset($item['component']) && $item['component'] === 'FormMultiColumn') {
                foreach ($item['items'] as $c => $col) {
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

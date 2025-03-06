<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Arr;
use Log;
use ProcessMaker\Cache\Screens\ScreenCache;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Models\Column;
use ProcessMaker\Models\Screen;

trait HasScreenFields
{
    private $parsedFields;

    private $restrictedComponents = [
        'FormImage',
    ];

    public function getFieldsAttribute()
    {
        if (empty($this->parsedFields)) {
            try {
                $this->loadScreenFields();
            } catch (\Throwable $e) {
                Log::error("Error encountered while retrieving fields for screen #{$this->id}", [
                    'message' => $e->getMessage(),
                    'screen' => $this->id,
                ]);
            } catch (\Exception $e) {
                Log::error("Error encountered while retrieving fields for screen #{$this->id}", [
                    'message' => $e->getMessage(),
                    'screen' => $this->id,
                ]);
            }
        }

        return $this->parsedFields->unique('field');
    }

    /**
     * Load the fields for the screen and cache them
     *
     * @return void
     */
    private function loadScreenFields()
    {
        $screenCache = ScreenCacheFactory::getScreenCache();
        // Create cache key
        $screenId = $this instanceof Screen ? $this->id : $this->screen_id;
        $screenVersionId = $this instanceof Screen ? 0 : $this->id;
        $key = $screenCache->createKey([
            'process_id' => 0,
            'process_version_id' => 0,
            'language' => 'all',
            'screen_id' => (int) $screenId,
            'screen_version_id' => (int) $screenVersionId,
        ]) . '_fields';

        // Try to get the screen fields from cache
        $parsedFields = $screenCache->get($key);

        if (!$parsedFields || collect($parsedFields)->isEmpty()) {
            $this->parsedFields = ScreenCache::makeFrom($this, []);
            if ($this->config) {
                $this->walkArray($this->config);
            }
            $this->parsedFields = ScreenCache::makeFrom($this, $this->parsedFields);

            $screenCache->set($key, $this->parsedFields);
        } else {
            $this->parsedFields = ScreenCache::makeFrom($this, $parsedFields);
        }
    }

    public function parseNestedScreen($node)
    {
        $nested = Screen::find($node['config']['screen']);
        foreach ($nested->fields as $field) {
            $this->parsedFields->push($field);
        }
    }

    public function parseCollectionRecordControl($node)
    {
        $collection = Screen::find($node['config']['collection']['screen']);
        foreach ($collection->fields as $field) {
            $this->parsedFields->push($field);
        }
    }

    public function walkArray($array, $key = null)
    {
        if (!is_array($array)) {
            $array = json_decode($array);
        }
        foreach ($array as $subkey => $value) {
            if (isset($value['component']) && $value['component'] === 'FormNestedScreen') {
                $this->parseNestedScreen($value);
            } elseif (isset($value['component']) && $value['component'] === 'FormCollectionRecordControl') {
                $this->parseCollectionRecordControl($value);
            } elseif ($key !== 'inspector' && is_array($value) && isset($value['config']['name'])) {
                $this->parseItem($value);
            }
            if (is_array($value)) {
                $this->walkArray($value, $subkey);
            }
        }
    }

    public function parseItem($item)
    {
        if (isset($item['component']) && !in_array($item['component'], $this->restrictedComponents)) {
            $this->parsedFields->push(new Column([
                'field' => $this->parseItemName($item),
                'label' => $this->parseItemLabel($item),
                'format' => $this->parseItemFormat($item),
                'mask' => $this->parseItemMask($item),
                'isSubmitButton' => $this->parseIsSubmitButton($item),
                'encryptedConfig' => $this->parseEncryptedConfig($item),
                'sortable' => true,
                'default' => false,
            ]));
        }
    }

    public function parseItemName($item)
    {
        return $item['config']['name'];
    }

    public function parseItemLabel($item)
    {
        if (isset($item['config']['label'])) {
            return $item['config']['label'];
        } else {
            return null;
        }
    }

    public function parseItemFormat($item)
    {
        $format = 'string';
        if (isset($item['config']['dataFormat'])) {
            $format = $item['config']['dataFormat'];
        }
        if (isset($item['component'])) {
            switch ($item['component']) {
                case 'FileUpload':
                case 'FileDownload':
                    $format = 'file';
                    break;
                case 'FormCheckbox':
                    $format = 'boolean';
                    break;
                case 'FormRecordList':
                    $format = 'array';
                    break;
                case 'FormSelectList':
                    $format = 'string';
                    if (isset($item['config']['options']['valueTypeReturned'])) {
                        if ($item['config']['options']['valueTypeReturned'] == 'object') {
                            $format = 'array';
                        }
                    }
                    break;
                case 'GooglePlaces':
                case 'SavedSearchChart':
                    $format = 'array';
                    break;
            }
        }

        return $format;
    }

    public function parseItemMask($item)
    {
        return $item['config']['dataMask'] ?? null;
        if (isset($item['config']['dataMask'])) {
        }
    }

    public function parseIsSubmitButton($item)
    {
        return Arr::get($item, 'config.event') === 'submit';
    }

    public function parseEncryptedConfig($item)
    {
        return $item['config']['encryptedConfig'] ?? null;
    }

    /**
     * Return an array of fields that can be included when
     * saving a draft or doing a quick fill, so as not to
     * overwrite fields not in the screen.
     *
     * @return array
     */
    public function screenFilteredFields()
    {
        return $this->fields->pluck('field');
    }
}

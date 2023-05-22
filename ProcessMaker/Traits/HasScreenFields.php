<?php

namespace ProcessMaker\Traits;

use Log;
use ProcessMaker\Models\Column;

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
                $this->parsedFields = collect([]);
                if ($this->config) {
                    $this->walkArray($this->config);
                }
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

    public function parseNestedScreen($node)
    {
        $nested = Screen::find($node['config']['screen']);
        foreach ($nested->fields as $field) {
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
}

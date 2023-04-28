<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Arr;

class Options
{
    const IMPORT_OPTIONS = [
        'mode' => [
            'enum' => [
                'update' => 'Update the existing asset with the one being imported',
                'discard' => 'Keep the existing asset and discard the one being imported',
                'copy' => 'Create a new asset and link dependent assets (in this import) to it',
            ],
            'default' => 'update',
        ],
        'isTemplate' => false,
        'saveAssetsMode' => ['saveModelOnly', 'saveAllAssets'],
    ];

    public $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    // Name can only equal "mode" for now
    public function get($name, $uuid)
    {
        $assetOptions = Arr::get($this->options, $uuid, []);

        $option = self::IMPORT_OPTIONS[$name];
        $importOptions = null;

        if ($name === 'mode') {
            $importOptions = self::IMPORT_OPTIONS[$name]['default'];
        } else {
            if (count($assetOptions) === 0) {
                $importOptions = self::IMPORT_OPTIONS[$name][1];
            } else {
                $importOptions = self::IMPORT_OPTIONS[$name];
            }
        }

        return Arr::get($assetOptions, $name, $importOptions);
    }
}

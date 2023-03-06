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

        return Arr::get($assetOptions, $name, self::IMPORT_OPTIONS[$name]['default']);
    }
}

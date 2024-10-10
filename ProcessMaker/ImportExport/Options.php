<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Arr;
use ProcessMaker\Exception\InvalidImportOption;

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
        'isTemplate' => [
            'enum' => [
                true => 'Asset is part of a template',
                false => 'Asset is not part of a template',
            ],
            'default' => false,
        ],
        'saveAssetsMode' => [
            'enum' => [
                'saveModelOnly' => 'Save only the model',
                'saveAllAssets' => 'Save all assets',
            ],
            'default' => 'saveAllAssets',
        ],
    ];

    public $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function get($name, $uuid)
    {
        $optionsForUuid = Arr::get($this->options, $uuid, []);

        $default = Arr::get(self::IMPORT_OPTIONS, $name . '.default', null);
        if ($default === null) {
            throw new InvalidImportOption($name);
        }

        if (Arr::has($optionsForUuid, $name)) {
            return Arr::get($optionsForUuid, $name, $default);
        }

        /**
         * Support for passing in global options like:
         *
         * new Options([
         *     'mode' => 'update',
         *     'saveAssetsMode' => 'saveModelOnly',
         *     'isTemplate' => false,
         * ]);
         *
         * Note that this is not the usual uuid => options map
         */

        return Arr::get($this->options, $name, $default);
    }
}

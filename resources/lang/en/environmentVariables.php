<?php

return [
    'validation' => [
        'name' => [
            'invalid_variable_name' => 'Name has to start with a letter and can contain only letters, numbers, and underscores (_).',
        ],
        'asset_type' => [
            'required_with' => 'Asset type is required when an asset is selected.',
        ],
        'asset_uuid' => [
            'required_with' => 'An asset must be selected when an asset type is set.',
        ],
    ],
];

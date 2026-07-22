<?php

return [
    'validation' => [
        'name' => [
            'invalid_variable_name' => 'Name has to start with a letter and can contain only letters, numbers, and underscores (_).',
        ],
        'asset_type' => [
            'required_with' => 'Asset type is required when an asset is selected.',
            'mismatch' => 'The selected asset does not match the specified asset type.',
        ],
        'asset_uuid' => [
            'required_with' => 'An asset must be selected when an asset type is set.',
            'not_found_for_type' => 'The selected asset could not be found for the specified asset type.',
        ],
        'value' => [
            'must_match_asset_id' => 'The value must match the ID of the selected asset.',
        ],
    ],
];

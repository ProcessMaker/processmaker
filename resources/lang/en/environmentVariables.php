<?php

return [
    'validation' => [
        'name' => [
            'invalid_variable_name' => 'Name has to start with a letter and can contain only letters, numbers, and underscores (_).',
        ],
        'asset_type' => [
            'mismatch' => 'The selected asset does not match the specified asset type.',
        ],
        'value' => [
            'required_with_asset_type' => 'A value (asset ID) is required when an asset type is set.',
            'not_found_for_type' => 'The selected asset could not be found for the specified asset type.',
            'must_match_asset_id' => 'The value must match the ID of the selected asset.',
        ],
    ],
];

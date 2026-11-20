<?php

return [
    // Only list resources that can no longer be imported, not all deprecated features.
    // Classes need not be installed: these rules inspect names in exported packages.
    'unsupported_assets' => [
        [
            'name' => 'Vocabulary',
            'classes' => [],
            'namespace_prefixes' => [
                'ProcessMaker\\Package\\PackageVocabularies',
            ],
        ],
    ],
];

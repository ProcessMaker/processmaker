<?php

use ProcessMaker\Package\PackageVocabularies\ImportExport\VocabularyExporter;
use ProcessMaker\Package\PackageVocabularies\Models\Vocabulary;
use ProcessMaker\Package\PackageVocabularies\Models\VocabularyVersion;

return [
    // A rule rejects every matching resource by default. The optional available_when
    // block lists the classes and tables needed to allow a matching resource. The
    // referenced class must also exist. Tables are checked on the active connection.
    'unsupported_assets' => [
        [
            'name' => 'Vocabulary',
            'classes' => [],
            'namespace_prefixes' => [
                'ProcessMaker\\Package\\PackageVocabularies',
            ],
            // Keep legacy Vocabulary imports working where the package and schema are
            // available. CI covers this through round-trip and PM Block publication tests.
            // Reject early when the destination lacks Vocabulary support (FOUR-34480).
            'available_when' => [
                'classes' => [Vocabulary::class, VocabularyVersion::class, VocabularyExporter::class],
                'tables' => ['vocabularies', 'vocabulary_versions', 'process_vocabulary'],
            ],
        ],
    ],
];

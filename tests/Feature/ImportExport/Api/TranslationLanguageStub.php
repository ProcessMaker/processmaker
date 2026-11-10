<?php

namespace ProcessMaker\Package\Translations\Models;

use Illuminate\Support\Collection;

/**
 * Loaded only when the optional translations package is not installed.
 * Export and preview call Language::select()->get() while building manifest
 * attributes. An empty list leaves translatedLanguages empty.
 */
class Language
{
    public static function select(mixed ...$columns): object
    {
        return new class {
            public function get(): Collection
            {
                return new Collection();
            }
        };
    }
}

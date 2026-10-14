<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Platform;

use ProcessMaker\Models\Screen;

final class ScreenCreationResult
{
    /**
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly Screen $screen,
        public readonly string $importMethod,
        public readonly array $warnings = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'screen' => $this->screen,
            'import_method' => $this->importMethod,
            'warnings' => $this->warnings,
        ];
    }
}

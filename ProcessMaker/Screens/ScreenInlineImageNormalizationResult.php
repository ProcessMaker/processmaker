<?php

namespace ProcessMaker\Screens;

class ScreenInlineImageNormalizationResult
{
    public function __construct(
        private readonly array $config,
        private readonly int $convertedCount,
        private readonly int $replacedCount = 0,
    ) {
    }

    public function config(): array
    {
        return $this->config;
    }

    public function convertedCount(): int
    {
        return $this->convertedCount;
    }

    public function replacedCount(): int
    {
        return $this->replacedCount;
    }

    public function wasModified(): bool
    {
        return $this->replacedCount > 0;
    }
}

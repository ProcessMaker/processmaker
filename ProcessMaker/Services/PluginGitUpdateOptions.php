<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

final class PluginGitUpdateOptions
{
    public function __construct(
        public readonly ?string $branch,
        public readonly ?string $tag,
        public readonly ?string $canonicalUrl,
        public readonly ?string $authenticatedUrl,
    ) {
    }
}

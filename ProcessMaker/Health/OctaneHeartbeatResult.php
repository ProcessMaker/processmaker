<?php

declare(strict_types=1);

namespace ProcessMaker\Health;

class OctaneHeartbeatResult
{
    public function __construct(
        public readonly bool $alive,
        public readonly string $mode,
        public readonly ?string $listener = null,
        public readonly ?string $endpoint = null,
        public readonly ?int $httpStatus = null,
        public readonly ?string $detail = null,
        public readonly ?string $hint = null,
        public readonly float $durationMs = 0.0,
        /** @var array<string, string>|null */
        public readonly ?array $phpIni = null,
        public readonly ?string $caddyfile = null,
        public readonly ?string $phpVersionWorker = null,
        /** @var array<string, bool>|null */
        public readonly ?array $extensionsWorker = null,
        /** @var list<array{name: string, status: string, detail: ?string}>|null */
        public readonly ?array $platformChecks = null,
    ) {
    }

    public static function aliveFromContext(
        ProbeContext $context,
        ?string $detail = null,
        ?int $httpStatus = null,
        ?array $phpIni = null,
        ?string $caddyfile = null,
        ?string $phpVersionWorker = null,
        ?array $extensionsWorker = null,
        ?array $platformChecks = null,
    ): self {
        return new self(
            alive: true,
            mode: $context->mode,
            listener: $context->listener,
            endpoint: $context->endpoint,
            httpStatus: $httpStatus,
            detail: $detail,
            durationMs: $context->elapsedMs(),
            phpIni: $phpIni,
            caddyfile: $caddyfile,
            phpVersionWorker: $phpVersionWorker,
            extensionsWorker: $extensionsWorker,
            platformChecks: $platformChecks,
        );
    }

    public static function deadFromContext(
        ProbeContext $context,
        string $detail,
        ?string $hint = null,
        ?int $httpStatus = null,
        ?array $platformChecks = null,
    ): self {
        return new self(
            alive: false,
            mode: $context->mode,
            listener: $context->listener,
            endpoint: $context->endpoint,
            httpStatus: $httpStatus,
            detail: $detail,
            hint: $hint,
            durationMs: $context->elapsedMs(),
            platformChecks: $platformChecks,
        );
    }

    public function exitCode(bool $verifyPhpIni): int
    {
        if (!$this->alive) {
            return 1;
        }

        if ($verifyPhpIni && $this->phpIni !== null && !CaddyPhpIni::allMatch($this->phpIni)) {
            return 1;
        }

        return 0;
    }
}

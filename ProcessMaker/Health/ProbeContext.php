<?php

declare(strict_types=1);

namespace ProcessMaker\Health;

final class ProbeContext
{
    public const MODE_LIVENESS = 'liveness';

    public const MODE_READINESS = 'readiness';

    public function __construct(
        public readonly string $mode,
        public readonly string $host,
        public readonly int $port,
        public readonly string $endpoint,
        public readonly float $timeout,
        public readonly string $listener,
        public readonly float $started,
    ) {
    }

    public static function create(bool $ready): self
    {
        $host = (string) config('octane.health.host', '127.0.0.1');
        $port = (int) config('octane.health.port', 8001);
        $endpoint = (string) config('octane.health.endpoint', '/health/live');

        return new self(
            mode: $ready ? self::MODE_READINESS : self::MODE_LIVENESS,
            host: $host,
            port: $port,
            endpoint: $endpoint,
            timeout: (float) config('octane.health.timeout', 2),
            listener: "{$host}:{$port}",
            started: microtime(true),
        );
    }

    public function elapsedMs(): float
    {
        return (microtime(true) - $this->started) * 1000;
    }
}

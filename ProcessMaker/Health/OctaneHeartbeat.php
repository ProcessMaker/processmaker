<?php

declare(strict_types=1);

namespace ProcessMaker\Health;

class OctaneHeartbeat
{
    public function probe(bool $ready = false, bool $includePhpIni = false): OctaneHeartbeatResult
    {
        $context = ProbeContext::create($ready);

        return $this->isRunningInsideOctaneWorker()
            ? $this->probeInsideWorker($context, $ready, $includePhpIni)
            : $this->probeExternalWorker($context, $ready, $includePhpIni);
    }

    private function probeInsideWorker(ProbeContext $context, bool $ready, bool $includePhpIni): OctaneHeartbeatResult
    {
        ['dead' => $dead, 'checks' => $checks] = PlatformHealthChecks::guard(
            $context,
            $ready,
            'Fix platform dependencies before accepting traffic.',
        );

        if ($dead !== null) {
            return $dead;
        }

        [$phpIni, $caddyfile, $phpVersion, $extensions] = CaddyPhpIni::workerSnapshot($includePhpIni);

        return OctaneHeartbeatResult::aliveFromContext(
            $context,
            detail: 'Current PHP process is an Octane worker.',
            phpIni: $phpIni,
            caddyfile: $caddyfile,
            phpVersionWorker: $phpVersion,
            extensionsWorker: $extensions,
            platformChecks: $checks,
        );
    }

    private function probeExternalWorker(ProbeContext $context, bool $ready, bool $includePhpIni): OctaneHeartbeatResult
    {
        if (!HealthEndpointProbe::isListenerOpen($context)) {
            return OctaneHeartbeatResult::deadFromContext(
                $context,
                "No listener on {$context->listener}.",
                hint: 'Start Octane / FrankenPHP or verify OCTANE_HEALTH_PORT.',
            );
        }

        $health = HealthEndpointProbe::fetch($context, $includePhpIni);

        if ($health['error'] !== null) {
            return OctaneHeartbeatResult::deadFromContext(
                $context,
                $health['error']['detail'],
                hint: $health['error']['hint'],
                httpStatus: $health['http_status'],
            );
        }

        ['dead' => $dead, 'checks' => $checks] = PlatformHealthChecks::guard(
            $context,
            $ready,
            'Octane is running but platform dependencies are unavailable.',
            $health['http_status'],
        );

        if ($dead !== null) {
            return $dead;
        }

        if ($includePhpIni && ($health['php_ini'] === null || $health['php_ini'] === [])) {
            return OctaneHeartbeatResult::deadFromContext(
                $context,
                'Worker php.ini could not be read from ' . $context->endpoint . '?format=json.',
                hint: 'Run `php artisan octane:reload` so workers expose /health/live?format=json.',
                httpStatus: $health['http_status'],
            );
        }

        return OctaneHeartbeatResult::aliveFromContext(
            $context,
            detail: "HTTP {$health['http_status']} from {$context->endpoint}",
            httpStatus: $health['http_status'],
            phpIni: $health['php_ini'],
            caddyfile: $health['caddyfile'],
            phpVersionWorker: $health['php_version_worker'],
            extensionsWorker: $health['extensions_worker'],
            platformChecks: $checks,
        );
    }

    private function isRunningInsideOctaneWorker(): bool
    {
        return function_exists('frankenphp_handle_request')
            || (isset($_SERVER['LARAVEL_OCTANE']) && (int) $_SERVER['LARAVEL_OCTANE'] === 1);
    }
}

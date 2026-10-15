<?php

declare(strict_types=1);

namespace ProcessMaker\Health;

use Illuminate\Support\Facades\Http;
use Throwable;

final class HealthEndpointProbe
{
    public static function isListenerOpen(ProbeContext $context): bool
    {
        $connection = @fsockopen(
            $context->host,
            $context->port,
            $errno,
            $errstr,
            min($context->timeout, 1.0),
        );

        if ($connection === false) {
            return false;
        }

        fclose($connection);

        return true;
    }

    /**
     * @return array{
     *     http_status: ?int,
     *     php_ini: ?array<string, string>,
     *     caddyfile: ?string,
     *     php_version_worker: ?string,
     *     extensions_worker: ?array<string, bool>,
     *     error: ?array{detail: string, hint: string}
     * }
     */
    public static function fetch(ProbeContext $context, bool $includePhpIni): array
    {
        $url = self::url($context, $includePhpIni);

        try {
            $request = Http::timeout($context->timeout)->withOptions(['allow_redirects' => false]);

            if ($includePhpIni) {
                $request = $request->acceptJson();
            }

            $response = $request->get($url);
            $httpStatus = $response->status();

            if ($httpStatus < 200 || $httpStatus >= 300) {
                return self::result(
                    $httpStatus,
                    error: [
                        'detail' => "{$context->endpoint} returned HTTP {$httpStatus}.",
                        'hint' => self::hintForHttpStatus($httpStatus, $context),
                    ],
                );
            }

            if (!$includePhpIni) {
                return self::result($httpStatus);
            }

            [$phpIni, $caddyfile, $phpVersion, $extensions] = self::parsePhpIniPayload(
                $response->json(),
                $response->body(),
            );

            return self::result(
                $httpStatus,
                phpIni: $phpIni,
                caddyfile: $caddyfile,
                phpVersionWorker: $phpVersion,
                extensionsWorker: $extensions,
            );
        } catch (Throwable $exception) {
            return self::result(error: [
                'detail' => $exception->getMessage(),
                'hint' => "Verify {$url} is reachable from this container.",
            ]);
        }
    }

    private static function url(ProbeContext $context, bool $includePhpIni): string
    {
        $url = 'http://' . $context->host . ':' . $context->port . '/' . ltrim($context->endpoint, '/');

        return $includePhpIni ? $url . '?format=json' : $url;
    }

    /**
     * @return array{
     *     http_status: ?int,
     *     php_ini: ?array<string, string>,
     *     caddyfile: ?string,
     *     php_version_worker: ?string,
     *     extensions_worker: ?array<string, bool>,
     *     error: ?array{detail: string, hint: string}
     * }
     */
    private static function result(
        ?int $httpStatus = null,
        ?array $phpIni = null,
        ?string $caddyfile = null,
        ?string $phpVersionWorker = null,
        ?array $extensionsWorker = null,
        ?array $error = null,
    ): array {
        return [
            'http_status' => $httpStatus,
            'php_ini' => $phpIni,
            'caddyfile' => $caddyfile,
            'php_version_worker' => $phpVersionWorker,
            'extensions_worker' => $extensionsWorker,
            'error' => $error,
        ];
    }

    private static function hintForHttpStatus(int $status, ProbeContext $context): string
    {
        if ($status === 302) {
            return 'Health route missing or Octane workers stale — run `php artisan octane:reload` and verify '
                . $context->endpoint . ' returns HTTP 200 on ' . $context->listener . '.';
        }

        return "Verify {$context->endpoint} returns HTTP 200 on {$context->listener}.";
    }

    /**
     * @return array{0: array<string, string>|null, 1: string|null, 2: string|null, 3: array<string, bool>|null}
     */
    private static function parsePhpIniPayload(mixed $payload, string $body = ''): array
    {
        if (!is_array($payload) && $body !== '') {
            $decoded = json_decode($body, true);
            $payload = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($payload)) {
            return [null, null, null, null];
        }

        $extensions = $payload['extensions'] ?? null;
        $metadata = [
            isset($payload['caddyfile']) ? (string) $payload['caddyfile'] : null,
            isset($payload['php_version']) ? (string) $payload['php_version'] : null,
            is_array($extensions) ? $extensions : null,
        ];
        $php = $payload['php'] ?? null;

        return is_array($php) && $php !== []
            ? [$php, ...$metadata]
            : [null, ...$metadata];
    }
}

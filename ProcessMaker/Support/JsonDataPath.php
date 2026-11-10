<?php

namespace ProcessMaker\Support;

use InvalidArgumentException;

/**
 * Builds a safe MySQL JSON path (e.g. $."a"."b") from user supplied key names.
 *
 * Every segment is validated against a strict allow-list and then wrapped in double quotes,
 * so key names can never be interpreted as path syntax. The returned path is meant to be
 * passed as a bound parameter to JSON_EXTRACT(), never interpolated into raw SQL.
 */
final class JsonDataPath
{
    /**
     * Any Unicode letter or number plus underscore, hyphen and space. Used by advanced filters.
     */
    public const FILTER_SEGMENT = '/^[\p{L}\p{N}_\- ]+$/u';

    /**
     * Variable-name style segments. Mirrors the modeler AgregationProperty.vue validation.
     */
    public const VARIABLE_SEGMENT = '/^[A-Za-z][0-9A-Za-z_$]*$/';

    /**
     * @param array<int, string> $segments
     *
     * @throws InvalidArgumentException when there are no segments or any segment is invalid
     */
    public static function fromSegments(array $segments, string $pattern = self::VARIABLE_SEGMENT): string
    {
        if (count($segments) === 0) {
            throw new InvalidArgumentException('The JSON path must contain at least one segment.');
        }

        foreach ($segments as $segment) {
            // Compare with \z semantics: reject a trailing newline that "$" would accept.
            if (!is_string($segment) || $segment === '' || str_contains($segment, "\n") || !preg_match($pattern, $segment)) {
                throw new InvalidArgumentException('Invalid JSON path segment.');
            }
        }

        return '$."' . implode('"."', $segments) . '"';
    }

    /**
     * @throws InvalidArgumentException when the dotted path is invalid
     */
    public static function fromDotNotation(string $path, string $pattern = self::VARIABLE_SEGMENT): string
    {
        return self::fromSegments(explode('.', $path), $pattern);
    }

    public static function isValid(?string $path, string $pattern = self::VARIABLE_SEGMENT): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        try {
            self::fromDotNotation($path, $pattern);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }
}

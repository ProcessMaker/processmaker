<?php

namespace ProcessMaker\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HttpOrigin implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = parse_url($value);
        if ($parts === false) {
            $fail(__('The instance URL must contain only an HTTP or HTTPS origin.'));

            return;
        }

        $path = $parts['path'] ?? '';
        if (
            !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || !in_array($path, ['', '/'], true)
            || isset($parts['query'], $parts['fragment'], $parts['user'], $parts['pass'])
        ) {
            $fail(__('The instance URL must contain only an HTTP or HTTPS origin.'));
        }
    }
}

<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

trait InteractsWithRawFilter
{
    private bool $usesRawValue = false;

    /**
     * Operators allowed to be used with raw()
     *
     * @var array
     */
    private array $validRawFilterOperators = [
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        'between',
    ];

    /**
     * Use regex to find the raw() pattern and extract its content
     *
     * @param  string|array|null  $value
     *
     * @return string
     */
    public function getRawValue(string|array $value = null): mixed
    {
        $value = $value ?? $this->value ?? '';

        if (!$this->containsRawValue($value)) {
            return '';
        }

        $match = static function (string $string) {
            return Str::match('/(?<=raw\().*(?=\))/', $string);
        };

        // If we receive a string, parse it and extract the
        // value set for the raw() query
        if (is_string($value)) {
            return $match($value);
        }

        // Otherwise, we have an array which we need to iterate
        // through and replace the values with the
        // raw() query string
        foreach ($value as $key => $string) {
            $value[$key] = $match($string);
        }

        return $value;
    }

    /**
     * Returns the parsed DB::raw() instance to apply to the query
     *
     * @return \Illuminate\Contracts\Database\Query\Expression
     */
    public function getParsedRawQueryValue(): mixed
    {
        $value = $this->getRawValue();

        // If wer have an array, we need to iterate and replace
        // the raw() query string with the filled
        // out DB::raw() instances
        if (is_array($value)) {
            foreach ($value as $key => $string) {
                $value[$key] = DB::raw($string);
            }
        } else {
            // Otherwise, we have a string for the raw()
            // value we can set and return
            $value = DB::raw($value);
        }

        return $value;
    }

    /**
     * Determine if the value is using the raw() function
     *
     * @param  string  $value
     *
     * @return bool
     */
    public function containsRawValue(string|array $value): bool
    {
        $containsRawValue = static function (string $string) {
            return Str::contains($string, 'raw(')
                && Str::endsWith($string, ')');
        };

        // If we receive a string, check it for the
        // special raw() filtering
        if (is_string($value)) {
            return $containsRawValue($value);
        }

        // Otherwise, if we receive an array, check for the raw() \
        // string to occur in ~any~ of its values
        foreach ($value as $string) {
            if ($containsRawValue($string)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets related properties
     *
     * @return void
     */
    protected function detectRawValue(): void
    {
        if ($this->usesRawValue = $this->containsRawValue($this->value ?? '')) {
            $this->validateOperator();
        }
    }

    /**
     * Returns true when this particular filter instance is using a raw() query filter
     *
     * @return bool
     */
    protected function filteringWithRawValue(): bool
    {
        return $this->usesRawValue === true;
    }

    /**
     * Validate the operator for this raw() filter
     *
     * @return bool
     */
    private function validateOperator(): void
    {
        $allowed = $this->validRawFilterOperators;

        if (!in_array($this->operator(), $allowed, true)) {
            abort(422, 'Invalid operator: Only '.implode(', ', $allowed). ' are allowed.');
        }
    }
}

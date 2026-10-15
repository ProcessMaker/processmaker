<?php

namespace ProcessMaker\Traits;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
trait InteractsWithRawFilter
{
    private const MAX_INTERVAL = 365;

    private bool $usesRawValue = false;

    /**
     * Operators allowed to be used with raw()
     *
     * @var array
     */
    private array $validRawFilterOperators = ['=', '!=', '>', '<', '>=', '<=', 'between'];

    /**
     * Unwrap the raw() and retrieve the string value passed
     *
     * @return Expression
     */
    public function getRawValue(): Expression
    {
        $value = $this->getValue();
        $matches = [];
        $pattern = '/^\s*raw\(\s*NOW\(\)\s*(?:(\+|-)\s*INTERVAL\s+([1-9]\d*)\s+'
            . '(SECOND|MINUTE|HOUR|DAY|WEEK|MONTH))?\s*\)\s*$/iD';

        if (!is_string($value) || preg_match($pattern, $value, $matches) !== 1) {
            abort(422, 'Invalid raw filter expression.');
        }

        if (isset($matches[2]) && (int) $matches[2] > self::MAX_INTERVAL) {
            abort(422, 'Raw filter interval exceeds the maximum allowed value.');
        }

        $expression = 'NOW()';
        if (isset($matches[1])) {
            $expression .= sprintf(
                ' %s INTERVAL %d %s',
                $matches[1],
                (int) $matches[2],
                strtoupper($matches[3])
            );
        }

        return DB::raw($expression);
    }

    /**
     * Determine if the value is using the raw() function
     *
     * @param  string  $value
     *
     * @return bool
     */
    public function containsRawValue(string $value): bool
    {
        return preg_match('/^\s*raw\s*\(/i', $value) === 1;
    }

    /**
     * Sets related properties
     *
     * @return void
     */
    protected function detectRawValue(): void
    {
        $values = is_array($this->getValue()) ? $this->getValue() : [$this->getValue()];
        $this->usesRawValue = false;

        if ($this->operator === 'between' && (!is_array($this->getValue()) || count($values) !== 2)) {
            abort(422, 'The between operator requires exactly two values.');
        }

        foreach ($values as $value) {
            if (is_string($value) && $this->containsRawValue($value)) {
                $this->usesRawValue = true;
                $this->validateOperator();
                $this->validateRawValue($value);
            }
        }
    }

    /**
     * Remove the initial "row(" and the final ")" to unwrap the filter value
     *
     * @param  string  $value
     *
     * @return string
     */
    protected function unwrapRawValue(string $value): string
    {
        return substr($value, 4, -1);
    }

    /**
     * Get the string value of the filter
     *
     * @return array|string
     */
    protected function getValue(): mixed
    {
        return $this->value ?? '';
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

    protected function valueWithRawExpressions(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->valueWithRawExpressions($item), $value);
        }

        if (is_string($value) && $this->containsRawValue($value)) {
            return $this->getRawValueFor($value);
        }

        return $value;
    }

    private function getRawValueFor(string $value): Expression
    {
        $originalValue = $this->value;
        $this->value = $value;

        try {
            return $this->getRawValue();
        } finally {
            $this->value = $originalValue;
        }
    }

    private function validateRawValue(string $value): void
    {
        $originalValue = $this->value;
        $this->value = $value;

        try {
            $this->getRawValue();
        } finally {
            $this->value = $originalValue;
        }
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
            abort(422, 'Invalid operator: Only ' . implode(', ', $allowed) . ' are allowed.');
        }
    }
}

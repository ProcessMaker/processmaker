<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Database\Query\Expression;

trait InteractsWithRawFilter
{
    private bool $usesRawValue = false;

    /**
     * Operators allowed to be used with raw()
     *
     * @var array
     */
    private array $validRawFilterOperators = ['=', '!=', '>', '<', '>=', '<='];

    /**
     * Unwrap the raw() and retrieve the string value passed
     *
     * @return \Illuminate\Contracts\Database\Query\Expression
     */
    public function getRawValue(): Expression
    {
        // Get the string equivalent of the raw() filter value
        $value = $this->containsRawValue($this->getValue()) ? $this->getValue() : '';

        // Remove the actual row( and ) from the string
        $unwrappedRawValue = $this->unwrapRawValue($value);

        // Wrap it in a DB expression and return it
        return DB::raw($unwrappedRawValue);
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
        return Str::contains($value, 'raw(')
            && Str::endsWith($value, ')');
    }

    /**
     * Sets related properties
     *
     * @return void
     */
    protected function detectRawValue(): void
    {
        $value = $this->getValue();

        // Sometimes, the value is an array, which likely means
        // this filter is set to the use the "between" operator
        $value = is_string($value) ? $value : '';

        // Detect if this particular filter includes a raw() value
        $this->usesRawValue = $this->containsRawValue($value);

        // If so, validate it is being used with a compatible operator
        if ($this->usesRawValue) {
            $this->validateOperator();
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
        $stripped = Str::after($value, 'raw(');

        return Str::beforeLast($stripped, ')');
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

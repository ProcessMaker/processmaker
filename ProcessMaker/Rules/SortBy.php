<?php

namespace ProcessMaker\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SortBy implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $items = explode(',', $value);

        foreach ($items as $item) {
            if (!preg_match('/^[a-zA-Z_]+:(asc|desc)$/', $item)) {
                $fail("The $attribute must be a comma-separated list of field:asc|desc.");
            }
        }
    }
}

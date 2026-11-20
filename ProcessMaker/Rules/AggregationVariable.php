<?php

namespace ProcessMaker\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use ProcessMaker\Support\JsonDataPath;

/**
 * Validates the variable name used to aggregate request data in the process stages.
 *
 * Accepts dot separated variable names (e.g. "amount", "grant.total"). Mirrors the modeler
 * validation in AgregationProperty.vue so the UI and the API agree.
 */
class AggregationVariable implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !JsonDataPath::isValid($value)) {
            $fail(__('The :attribute must be a valid variable name.', ['attribute' => $attribute]));
        }
    }
}

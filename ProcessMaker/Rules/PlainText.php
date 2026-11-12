<?php

namespace ProcessMaker\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PlainText implements ValidationRule
{
    /**
     * Validate that the value does not contain HTML markup.
     *
     * @param string  $attribute Attribute being validated.
     * @param mixed   $value     Value being validated.
     * @param Closure $fail      Validation failure callback.
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || strip_tags($value) !== $value) {
            $fail('The :attribute field must contain plain text only.');
        }
    }
}

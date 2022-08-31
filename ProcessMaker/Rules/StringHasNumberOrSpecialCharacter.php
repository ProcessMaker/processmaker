<?php

namespace ProcessMaker\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class StringHasNumberOrSpecialCharacter implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return preg_match('/[^a-zA-Z\d]/', $value) || preg_match('/\d/', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must contain either a number or a special character.';
    }
}

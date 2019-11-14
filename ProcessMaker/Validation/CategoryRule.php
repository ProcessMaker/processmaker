<?php
namespace ProcessMaker\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\QueryException;

class CategoryRule implements Rule
{
    public function passes($attribute, $value)
    {
        $type = explode('_', $attribute)[0];
        $class = '\\ProcessMaker\\Models\\' . ucfirst($type) . 'Category';
        if (strpos($value, ',') !== false) {
            $ids = explode(",", $value);
        } else {
            $ids = [$value];
        }
        foreach($ids as $id) {
            if (!$class::where('id', $id)->exists()) {
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'Invalid category';
    }
}
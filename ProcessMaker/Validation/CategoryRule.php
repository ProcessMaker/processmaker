<?php
namespace ProcessMaker\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

/**
 * Must implement ImplicitRule because this always needs
 * to be run, even if the field is empty.
 */
class CategoryRule implements ImplicitRule 
{
    public function __construct($model) {
        $this->model = $model;
    }

    public function passes($attribute, $value)
    {
        $type = explode('_', $attribute)[0];
        $class = '\\ProcessMaker\\Models\\' . ucfirst($type) . 'Category';

        /**
         * If the model has previously been saved to the database,
         * it's safe to assume that the category exists and is not
         * a required parameter for an update.
         */
        if (empty($value)) {
            if ($this->model && $this->model->exists) {
                return true;
            } else {
                validator()->validate([], [$attribute => 'required']);
            }
        }

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
<?php

namespace ProcessMaker\Traits;

use Illuminate\Validation\ValidationException;
use ProcessMaker\Support\JsonDataPath;

/**
 * Guards the `aggregation` attribute of process models.
 *
 * The value ends up addressing a JSON key of the request data, so it must be a plain variable
 * name. Validating at the model level covers every write path (API, import, versioning) even
 * when request validation is bypassed or replaced.
 */
trait HasAggregationAttribute
{
    /**
     * @throws ValidationException when the value is not a valid variable name
     */
    public function setAggregationAttribute(?string $value): void
    {
        if ($value !== null && $value !== '' && !JsonDataPath::isValid($value)) {
            throw ValidationException::withMessages([
                'aggregation' => __('The :attribute must be a valid variable name.', ['attribute' => 'aggregation']),
            ]);
        }

        $this->attributes['aggregation'] = $value;
    }
}

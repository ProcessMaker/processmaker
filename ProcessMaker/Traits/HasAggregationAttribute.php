<?php

namespace ProcessMaker\Traits;

use Illuminate\Validation\ValidationException;
use ProcessMaker\Models\Process;
use ProcessMaker\Support\JsonDataPath;

/**
 * Guards the `aggregation` attribute of process models.
 *
 * The value ends up addressing a JSON key of the request data, so it must be a plain variable
 * name. New writes are rejected. A copy of a value already stored before that rule existed
 * (a version snapshot or a duplicated process) is replaced with the default instead, so
 * saving BPMN is not blocked by a legacy row.
 */
trait HasAggregationAttribute
{
    /**
     * @throws ValidationException when a new value is not a valid variable name
     */
    public function setAggregationAttribute(?string $value): void
    {
        $this->attributes['aggregation'] = static::prepareAggregation(
            $value,
            $this->rejectsInvalidAggregation()
        );
    }

    /**
     * Process rejects invalid names. ProcessVersion does not: every save copies the current
     * aggregation onto a version, including values stored before validation existed.
     */
    protected function rejectsInvalidAggregation(): bool
    {
        return true;
    }

    /**
     * @throws ValidationException when $rejectInvalid is true and the value is not a variable name
     */
    public static function prepareAggregation(?string $value, bool $rejectInvalid = true): ?string
    {
        if ($value === null || $value === '' || JsonDataPath::isValid($value)) {
            return $value;
        }

        if ($rejectInvalid) {
            throw ValidationException::withMessages([
                'aggregation' => __('The :attribute must be a valid variable name.', ['attribute' => 'aggregation']),
            ]);
        }

        return Process::DEFAULT_AGGREGATION;
    }
}

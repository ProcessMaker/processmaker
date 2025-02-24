<?php

namespace ProcessMaker\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class MillisecondsToDateCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     *
     * @return Carbon|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Carbon|null
    {
        return $value ? Carbon::createFromTimestampMs($value) : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     *
     * @return float|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): float|null
    {
        return $value ? Carbon::parse($value)->valueOf() : null;
    }
}

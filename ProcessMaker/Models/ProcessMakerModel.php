<?php

namespace ProcessMaker\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class that all models should extend from.
 */
class ProcessMakerModel extends Model
{
    use HasFactory;

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function scopeExclude($query, ...$columns)
    {
        return $columns !== [] ? $query->select(
            array_diff(
                $this->getTableColumns(),
                \Illuminate\Support\Arr::flatten($columns)
            )
        ) : $query;
    }

    public function getTableColumns()
    {
        return \Illuminate\Support\Facades\Cache::rememberForever(
            'MigrMod:'.filemtime(
                database_path('migrations')).':'.$this->getTable(), function () {
                    return $this->getConnection()
                        ->getSchemaBuilder()
                        ->getColumnListing($this->getTable());
                }
            );
    }
}

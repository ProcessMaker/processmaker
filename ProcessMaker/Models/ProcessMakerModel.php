<?php

namespace ProcessMaker\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Base class that all models should extend from.
 */
class ProcessMakerModel extends Model
{
    use HasFactory;

    const MIGRATION_COLUMNS_CACHE_KEY = 'migration_columns';

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
                Arr::flatten($columns)
            )
        ) : $query;
    }

    public function getTableColumns()
    {
        $key = 'MigrMod:' . $this->getTable();

        return Cache::tags(static::MIGRATION_COLUMNS_CACHE_KEY)->rememberForever(
            $key, function () {
                return $this->getConnection()
                    ->getSchemaBuilder()
                    ->getColumnListing($this->getTable());
            }
        );
    }
}

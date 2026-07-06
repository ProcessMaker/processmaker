<?php

namespace ProcessMaker\CaseRetention;

use Illuminate\Database\Eloquent\Builder;

final class CaseRetentionLogQueryFilter
{
    public static function applyIfFilled(Builder $query, ?string $filter): void
    {
        if ($filter === null || trim($filter) === '') {
            return;
        }

        self::apply($query, trim($filter));
    }

    /**
     * Search log id, process_id, numeric columns, and JSON case_ids — not date columns.
     */
    public static function apply(Builder $query, string $term): void
    {
        $like = '%' . $term . '%';
        $driver = $query->getConnection()->getDriverName();

        $query->where(function ($q) use ($like, $driver) {
            $q->where('id', 'like', $like)
                ->orWhere('process_id', 'like', $like)
                ->orWhere('deleted_count', 'like', $like)
                ->orWhere('total_time_taken', 'like', $like);

            if ($driver === 'pgsql') {
                $q->orWhereRaw('case_ids::text ILIKE ?', [$like]);
            } else {
                $q->orWhereRaw('CAST(case_ids AS CHAR) LIKE ?', [$like]);
            }
        });
    }
}

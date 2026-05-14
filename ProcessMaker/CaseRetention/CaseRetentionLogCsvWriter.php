<?php

namespace ProcessMaker\CaseRetention;

use Illuminate\Database\Eloquent\Builder;

final class CaseRetentionLogCsvWriter
{
    /**
     * Stream CSV rows to a writable stream (no column header row). UTF-8 BOM prepended.
     *
     * @param resource $stream
     */
    public static function writeQueryToStream(Builder $query, $stream): void
    {
        fwrite($stream, "\xEF\xBB\xBF");

        $query->clone()->chunkById(500, function ($rows) use ($stream) {
            foreach ($rows as $row) {
                $caseIds = $row->case_ids;
                if (is_array($caseIds)) {
                    $caseIds = json_encode($caseIds);
                }

                fputcsv($stream, [
                    $row->id,
                    $row->process_id,
                    $caseIds,
                    $row->deleted_count,
                    $row->total_time_taken,
                    self::csvDateColumn($row->deleted_at),
                    self::csvDateColumn($row->created_at),
                ]);
            }
        });
    }

    public static function csvDateColumn(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}

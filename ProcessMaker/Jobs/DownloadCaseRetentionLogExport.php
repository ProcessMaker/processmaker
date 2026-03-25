<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use ProcessMaker\CaseRetention\CaseRetentionLogCsvWriter;
use ProcessMaker\CaseRetention\CaseRetentionLogQueryFilter;
use ProcessMaker\Events\CaseRetentionLogExportFailed;
use ProcessMaker\Events\CaseRetentionLogExportReady;
use ProcessMaker\Models\CaseRetentionPolicyLog;
use ProcessMaker\Models\User;
use Throwable;

class DownloadCaseRetentionLogExport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const LINK_TTL_HOURS = 24;

    public function __construct(
        private User $user,
        private ?string $filter,
        private string $exportToken,
    ) {
    }

    public function getFilter(): ?string
    {
        return $this->filter;
    }

    public function handle(): void
    {
        if (!Str::isUuid($this->exportToken)) {
            event(new CaseRetentionLogExportFailed($this->user, false, 'Invalid export token.'));

            return;
        }

        $relativePath = 'exports/case-retention/' . $this->exportToken . '.csv';

        try {
            Storage::disk('local')->makeDirectory('exports/case-retention');

            $fullPath = Storage::disk('local')->path($relativePath);
            $handle = fopen($fullPath, 'w');
            if ($handle === false) {
                throw new \RuntimeException('Could not open export file for writing.');
            }

            try {
                $query = CaseRetentionPolicyLog::query();
                CaseRetentionLogQueryFilter::applyIfFilled($query, $this->filter);
                CaseRetentionLogCsvWriter::writeQueryToStream($query, $handle);
            } finally {
                fclose($handle);
            }

            $expires = now()->addHours(self::LINK_TTL_HOURS);
            $url = URL::temporarySignedRoute(
                'api.cases-retention.logs.export.download',
                $expires,
                ['token' => $this->exportToken],
            );

            $message = __('Click on the link to download the log file. This link will be available until ' . $expires->toString());

            event(new CaseRetentionLogExportReady($this->user, true, $message, $url));
        } catch (Throwable $e) {
            Storage::disk('local')->delete($relativePath);
            event(new CaseRetentionLogExportFailed($this->user, false, $e->getMessage()));
        }
    }
}

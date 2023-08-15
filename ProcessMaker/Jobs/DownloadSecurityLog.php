<?php

namespace ProcessMaker\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProcessMaker\Events\SecurityLogDownloadFailed;
use ProcessMaker\Events\SecurityLogDownloadJobCompleted;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\User;
use Ramsey\Uuid\Uuid;

class DownloadSecurityLog implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private User $user;

    private string $format;

    private ?int $userId;

    public const CSV_SEPARATOR = ',';

    public const EXPIRATION_HOURS = 24;

    public const FORMAT_CSV = 'csv';

    public const FORMAT_XML = 'xml';

    /**
     * @param User $user
     * @param string $format xml|csv
     * @param int|null $userId
     */
    public function __construct(User $user, string $format, int $userId = null)
    {
        $this->user = $user;
        $this->format = $format;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throw Exception
     */
    public function handle()
    {
        // Check if the S3 is ready to use
        if (!Media::s3IsReady()) {
            event(new SecurityLogDownloadFailed($this->user, false, __('This feature requires the configured AWS S3 service. Please contact your Customer Success Manager to use it.')));

            return;
        }
        try {
            // Get the temp filename
            $filename = $this->createTemporaryFilename();
            // Get the date of expiration
            $expires = $this->getExpires();
            // Export the file and get the URL
            $url = $this->export($filename, $expires);
            $message = __('Click on the link to download the log file. This link will be available until ' . $expires->toString());
            // Call the event
            event(new SecurityLogDownloadJobCompleted($this->user, true, $message, $url));
        } catch (Exception $e) {
            $message = __('It was not possible to connect AWS S3 service. Please contact your Customer Success Manager to use it.');
            event(new SecurityLogDownloadFailed($this->user, false, $e->getMessage()));
        }
    }

    /**
     * Get expires time
     *
     * @return Carbon time
     */
    protected function getExpires()
    {
        return now()->addHours(static::EXPIRATION_HOURS);
    }

    /**
     * Create a temp file
     *
     * @return string
     */
    protected function createTemporaryFilename()
    {
        $uuid = Uuid::uuid4()->toString() . Str::random(8);

        $s3Uri = empty(config('app.security_log_s3_uri')) ? 'security-logs' : config('app.security_log_s3_uri');

        return $s3Uri .'/'. $uuid . '.' . $this->format;
    }

    /**
     * Export the file and get the URL
     *
     * @param string $filename
     * @param Carbon $expires
     *
     * @return URL
     */
    protected function export(string $filename, Carbon $expires)
    {
        // Get a disk manager for S3
        $disk = Storage::disk('s3');

        // Create a stream
        $stream = fopen('php://temp', 'w+');

        // Write the content
        $stream = $this->writeContent($stream);

        // Rewind the stream
        rewind($stream);

        // Save the stream to S3
        $disk->put($filename, stream_get_contents($stream), [
            'ACL' => 'private', // private|public-read,
            'Expires' => $expires->toString(),
        ]);

        // Close the stream
        fclose($stream);

        // Save temporary Url
        $url = $disk->temporaryUrl(
            $filename,
            $expires,
            [
                'ResponseContentType' => 'application/octet-stream',
                'ResponseContentDisposition' => 'attachment; filename=' . $filename,
            ]
        );

        return $url;
    }

    /**
     * Generate the content according to the format
     *
     * @return string
     */
    protected function writeContent($stream)
    {
        $query = DB::table('security_logs');

        // Check the filter per user
        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        // Initial tags for XML
        $this->initialTagsXML($this->format === static::FORMAT_XML, $stream);

        // Use a cursor to iterate over the table data
        $query->orderBy('id')->cursor()->each(function ($record) use ($stream) {
            // Convert each record to an array and write it to the stream
            $stream = $this->format === static::FORMAT_CSV ? $this->toCSV($stream, (array) $record) : $this->toXML($stream, (array) $record);
        });

        // End tags for XML
        $this->endTagsXML($this->format === static::FORMAT_XML, $stream);

        return $stream;
    }

    /**
     * Write the CSV line
     *
     * @param string $stream
     * @param array $record
     *
     * @return string
     */
    protected function toCSV($stream, array $record)
    {
        fputcsv($stream, (array) $record, static::CSV_SEPARATOR);

        return $stream;
    }

    /**
     * Write the XML node
     *
     * @param string $stream
     * @param array $record
     *
     * @return string
     */
    protected function toXML($stream, array $record)
    {
        $content = $this->getXmlNode((array) $record);
        fwrite($stream, $content);

        return $stream;
    }

    /**
     * Write the initial tags to XML
     *
     * @param bool $write
     * @param string $stream
     *
     * @return void
     */
    protected function initialTagsXML($write, $stream)
    {
        if ($write) {
            $contentXml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $contentXml .= '<securityLogs>';
            fwrite($stream, $contentXml);
        }
    }

    /**
     * Write the end tags to XML
     *
     * @param bool $write
     * @param string $stream
     *
     * @return void
     */
    protected function endTagsXML($write, $stream)
    {
        if ($write) {
            $contentXml = PHP_EOL . '</securityLogs>';
            fwrite($stream, $contentXml);
        }
    }

    /**
     * Get XML node
     *
     * @param array $item
     *
     * @return string
     */
    protected function getXmlNode(array $item)
    {
        $tab = "\t";
        $content = PHP_EOL . $tab . '<securityLog>';
        foreach ($item as $key => $value) {
            if (is_object($value)) {
                $value = json_encode($value);
            }
            $content .= sprintf(
                '%s<%s>%s</%s>',
                PHP_EOL . $tab,
                $key,
                $value,
                $key
            );
        }
        $content .= PHP_EOL . $tab . '</securityLog>';

        return $content;
    }
}

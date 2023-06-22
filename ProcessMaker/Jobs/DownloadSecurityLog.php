<?php

namespace ProcessMaker\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public const CSV_SEPARATOR = '|';

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
        if (!Media::s3IsReady()) {
            event(new SecurityLogDownloadJobCompleted($this->user, false, __('Sorry, this feature requires the configured AWS S3 service.. Please contact the administrator.')));
            return;
        }
        try {
            $collection = $this->getCollection();
            $contents = $this->generateContent($collection);
            $expires = $this->getExpires();
            $filename = $this->createTemporaryFilename();
            $url = $this->createTemporaryUrl($filename, $contents, $expires);
            $message = __('Click on the link and download the file. This link will be available until midnight tonight.');
            event(new SecurityLogDownloadJobCompleted($this->user, true, $message, $url));
        } catch (Exception $e) {
            $message = __('Sorry, it was not possible to generate the log file. Please contact the administrator.');
            event(new SecurityLogDownloadJobCompleted($this->user, false, $message));
        }
    }

    /**
     * Generate the content with the corresponding format
     */
    protected function generateContent(array $collection)
    {
        return $this->format === static::FORMAT_CSV ? $this->toCSV($collection) : $this->toXML($collection);
    }

    /**
     * To CSV
     */
    protected function toCSV(array $collection)
    {
        $first = current($collection);
        $headers = array_keys($first ?: []);
        $content = '';
        $line = [];
        foreach ($headers as $key) {
            $line[] = $key;
        }
        $content .= implode(static::CSV_SEPARATOR, $line);
        foreach ($collection as $item) {
            $line = [];
            foreach ($headers as $key) {
                if (is_object($item[$key])) {
                    $line[] = json_encode($item[$key]);
                } else {
                    $line[] = isset($item[$key]) ? $item[$key] : null;
                }
            }
            $content .= PHP_EOL . implode(static::CSV_SEPARATOR, $line);
        }
        return $content;
    }

    /**
     * To XML
     */
    protected function toXML(array $collection)
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $content .= '<securityLogs>';
        $tab = "\t";
        foreach ($collection as $item) {
            $content .= PHP_EOL . $tab . '<securityLog>';
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
        }
        $content .= PHP_EOL . '</securityLogs>';

        return $content;
    }

    /**
     * Get collection
     *
     * @return array
     */
    protected function getCollection()
    {
        $query = SecurityLog::query();
        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }
        $results = [];
        $query->chunk(1000, function (Collection $collection) use (&$results) {
            foreach ($collection as $item) {
                $results[] = $item->toArray();
            }
            unset($item);
        });

        return $results;
    }

    /**
     * Create temp url
     *
     * @param string $filename
     * @param string $content
     * @param Carbon $expires
     *
     * @return string
     */
    protected function createTemporaryUrl(string $filename, string $content, Carbon $expires)
    {
        $disk = Storage::disk('s3');
        $disk->put($filename, $content, [
            'ACL' => 'private', // private|public-read,
            'Expires' => $expires->toString()
        ]);
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
     * Get expires time
     *
     * @return Carbon time
     */
    protected function getExpires()
    {
        return now()->addHours(24);
    }

    /**
     * Create a temp file
     *
     * @return string
     */
    protected function createTemporaryFilename()
    {
        $uuid = Uuid::uuid4()->toString() . Str::random(8);

        return 'security-logs/' . $uuid . '.'  . $this->format;
    }
}

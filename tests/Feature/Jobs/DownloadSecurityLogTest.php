<?php

namespace Tests\Feature\Jobs;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\Events\SecurityLogDownloadJobCompleted;
use ProcessMaker\Jobs\DownloadSecurityLog;
use ProcessMaker\Models\SecurityLog;
use ReflectionMethod;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DownloadSecurityLogTest extends TestCase
{
    use RequestHelper;

    protected $simpleCollection;

    protected function withUserSetup(): void
    {
        SecurityLog::query()->delete();

        $this->simpleCollection = [
            ['id' => 1, 'event' => 'login'],
            ['id' => 2, 'event' => 'logout'],
        ];

        SecurityLog::factory()->create(['event' => 'login', 'user_id' => $this->user->id]);
        SecurityLog::factory()->create(['event' => 'logout', 'user_id' => $this->user->id]);
        SecurityLog::factory()->create(['event' => 'attempt']);
        SecurityLog::factory()->create(['event' => 'attempt']);
    }

    public function testCreateTemporaryFilename()
    {
        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
        $method = new ReflectionMethod($job, 'createTemporaryFilename');
        $filename = $method->invoke($job);
        $this->assertStringContainsString('.csv', $filename);

        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_XML);
        $method = new ReflectionMethod($job, 'createTemporaryFilename');
        $filename = $method->invoke($job);
        $this->assertStringContainsString('.xml', $filename);
    }

    public function testExpires()
    {
        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
        $method = new ReflectionMethod($job, 'getExpires');
        $expires = $method->invoke($job);
        $this->assertLessThan($expires, now());

        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_XML);
        $method = new ReflectionMethod($job, 'getExpires');
        $expires = $method->invoke($job);
        $this->assertLessThan($expires, now());
    }

    /**
     * @covers DownloadSecurityLog::toCSV
     */
    public function testWriteContentCSV()
    {
        $stream = fopen('php://temp', 'w+');
        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
        $csv = (new ReflectionMethod($job, 'writeContent'))->invoke($job, $stream);
        $this->assertNotEmpty($csv);
        $this->assertTrue(rewind($stream));
        $this->assertTrue(fclose($stream));
    }

    /**
     * @covers DownloadSecurityLog::initialTagsXML
     * @covers DownloadSecurityLog::toXML
     * @covers DownloadSecurityLog::endTagsXML
     */
    public function testWriteContentXML()
    {
        $stream = fopen('php://temp', 'w+');
        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_XML);
        $xml = (new ReflectionMethod($job, 'writeContent'))->invoke($job, $stream);
        $this->assertNotEmpty($xml);
        $this->assertTrue(rewind($stream));
        $this->assertTrue(fclose($stream));
    }

    public function testHandleWithSuccess()
    {
        if (
            !config('filesystems.disks.s3.key')
            && !config('filesystems.disks.s3.secret')
            && !config('filesystems.disks.s3.region')
            && !config('filesystems.disks.s3.bucket')
        ) {
            $this->markTestSkipped(
                'AWS S3 service is not available.'
            );
        } else {
            $this->expectsEvents(SecurityLogDownloadJobCompleted::class);
            $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
            $url = (new ReflectionMethod($job, 'handle'))->invoke($job);
            $this->assertNotEmpty($url);
            $data = file_get_contents($url);
            $this->assertNotEmpty($data);
        }
    }

    public function testExport()
    {
        if (
            !config('filesystems.disks.s3.key')
            && !config('filesystems.disks.s3.secret')
            && !config('filesystems.disks.s3.region')
            && !config('filesystems.disks.s3.bucket')
        ) {
            $this->markTestSkipped(
                'AWS S3 service is not available.'
            );
        } else {
            $this->expectsEvents(SecurityLogDownloadJobCompleted::class);
            $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
            $filename = (new ReflectionMethod($job, 'createTemporaryFilename'))->invoke($job);
            $expires = (new ReflectionMethod($job, 'getExpires'))->invoke($job);
            $url = (new ReflectionMethod($job, 'export'))->invoke($job, $filename, $expires);
            $this->assertNotEmpty($url);
            $data = file_get_contents($url);
            $this->assertNotEmpty($data);
        }
    }
}

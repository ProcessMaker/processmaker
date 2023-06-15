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

    public function testGetCollection()
    {
        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
        $results = (new ReflectionMethod($job, 'getCollection'))->invoke($job);
        $this->assertCount(4, $results);

        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV, $this->user->id);
        $results = (new ReflectionMethod($job, 'getCollection'))->invoke($job);
        $this->assertCount(2, $results);
    }

    public function testToCSV()
    {
        $collection = $this->simpleCollection;
        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
        $csv = (new ReflectionMethod($job, 'toCSV'))->invoke($job, $collection);
        $this->assertEquals(
            'id|event' . PHP_EOL .
            '1|login' . PHP_EOL .
            '2|logout',
            $csv
        );
    }

    public function testToXML()
    {
        $collection = $this->simpleCollection;
        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
        $xml = (new ReflectionMethod($job, 'toXML'))->invoke($job, $collection);
        $this->assertStringContainsString('<securityLog>', $xml);
        $this->assertStringContainsString('<id>2</id>', $xml);
    }

    public function testCreateTemporaryUrl()
    {
        //The test should not be run as it requires an AWS key
        $this->assertTrue(true, 'ignoring');
        return;
        $collection = $this->simpleCollection;
        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
        $filename = (new ReflectionMethod($job, 'createTemporaryFilename'))->invoke($job);
        $expires = (new ReflectionMethod($job, 'getExpires'))->invoke($job);
        $csv = (new ReflectionMethod($job, 'toCSV'))->invoke($job, $collection);
        $url = (new ReflectionMethod($job, 'createTemporaryUrl'))->invoke($job, $filename, $csv, $expires);
        $this->assertStringContainsString('s3.amazonaws.com/security-logs', $url);
        $disk = Storage::disk('s3');
        $this->assertTrue($disk->exists($filename));
    }

    public function testHandleWithSuccess()
    {
        //The test should not be run as it requires an AWS key
        $this->assertTrue(true, 'ignoring');
        return;
        $this->expectsEvents(SecurityLogDownloadJobCompleted::class);
        $job = new DownloadSecurityLog($this->user, DownloadSecurityLog::FORMAT_CSV);
        (new ReflectionMethod($job, 'handle'))->invoke($job);
    }
}

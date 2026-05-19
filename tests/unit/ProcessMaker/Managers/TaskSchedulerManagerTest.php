<?php

namespace ProcessMaker\Managers;

use Carbon\Carbon;
use DateTime;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Setting;
use ReflectionMethod;
use Tests\TestCase;

class TaskSchedulerManagerTest extends TestCase
{
    /**
     * @var TaskSchedulerManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new TaskSchedulerManager;
    }

    public function testRoundDates()
    {
        // Exact minute:                 00:01:00 -> 00:01:00
        $date = new DateTime('2019-08-14 00:01:00');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 10 seconds:            00:01:10 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:10');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 29 seconds:            00:01:29 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:29');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 30 seconds:            00:01:30 -> 00:02:00
        $date = new Carbon('2019-08-14 00:01:30');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:02:00', $rounded->format('H:i:s'));

        // with 59 seconds:            00:01:59 -> 00:02:00
        $date = new Carbon('2019-08-14 00:01:59');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:02:00', $rounded->format('H:i:s'));
    }

    public function testTruncateDates()
    {
        // Exact minute:                 00:01:00 -> 00:01:00
        $date = new DateTime('2019-08-14 00:01:00');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 10 seconds:            00:01:10 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:10');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 29 seconds:            00:01:29 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:29');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 30 seconds:            00:01:30 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:30');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 59 seconds:            00:01:59 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:59');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));
    }

    public function testHasAdequateAbeInboundConfigurationForStandardAuth()
    {
        Setting::updateOrCreate(['key' => 'abe_imap_auth_method'], ['config' => '0']);
        Setting::updateOrCreate(['key' => 'abe_imap_username'], ['config' => 'abe@test.com']);
        Setting::updateOrCreate(['key' => 'abe_imap_password'], ['config' => '123Test']);
        Setting::updateOrCreate(['key' => 'abe_imap_server'], ['config' => 'imap.example.com']);
        Setting::updateOrCreate(['key' => 'abe_imap_port'], ['config' => '993']);

        $this->assertTrue((bool) $this->invokePrivateMethod('hasAdequateAbeInboundConfiguration'));
    }

    public function testHasAdequateAbeInboundConfigurationForGoogleOauth()
    {
        Setting::updateOrCreate(['key' => 'abe_imap_auth_method'], ['config' => '1']);
        Setting::updateOrCreate(['key' => 'abe_imap_username'], ['config' => 'abe@test.com']);

        foreach ([
            'ABE_GMAIL_API_CLIENT_ID',
            'ABE_GMAIL_API_SECRET',
            'ABE_GMAIL_API_ACCESS_TOKEN',
            'ABE_GMAIL_API_REFRESH_TOKEN',
        ] as $name) {
            EnvironmentVariable::factory()->create([
                'name' => $name,
                'value' => 'value-' . strtolower($name),
            ]);
        }

        $this->assertTrue((bool) $this->invokePrivateMethod('hasAdequateAbeInboundConfiguration'));
    }

    public function testHasAdequateAbeInboundConfigurationReturnsFalseWhenUsernameIsMissing()
    {
        Setting::updateOrCreate(['key' => 'abe_imap_auth_method'], ['config' => '0']);
        Setting::updateOrCreate(['key' => 'abe_imap_username'], ['config' => '']);
        Setting::updateOrCreate(['key' => 'abe_imap_password'], ['config' => '123Test']);
        Setting::updateOrCreate(['key' => 'abe_imap_server'], ['config' => 'imap.example.com']);
        Setting::updateOrCreate(['key' => 'abe_imap_port'], ['config' => '993']);

        $this->assertFalse((bool) $this->invokePrivateMethod('hasAdequateAbeInboundConfiguration'));
    }

    public function testIsHandleRepliesProcessUsesPackageKey()
    {
        $process = new Process([
            'package_key' => 'package-actions-by-email/handle-replies',
            'name' => 'Anything',
        ]);

        $this->assertTrue((bool) $this->invokePrivateMethod('isHandleRepliesProcess', [$process]));
    }

    private function invokePrivateMethod(string $method, array $args = [])
    {
        $reflection = new ReflectionMethod($this->manager, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($this->manager, $args);
    }
}

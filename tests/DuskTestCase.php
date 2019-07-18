<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

/**
 * The base of our dusk tests. Note it uses database migrations followed
 * by database seeding.  This is slow but ensures clean execution between
 * tests.
 */
abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseMigrations;

    /**
     * Create a fresh database instance before each test class is initialized. This is different
     * than the default DatabaseMigrations as it is only run when the class is setup. The trait
     * provided by Laravel will run on EACH test function, slowing things down significantly.
     *
     * If you need to reset the DB between function runs just include the trait in that specific
     * test. In most cases you probably wont need to do this, or can modify the test slightly to
     * avoid the need to do so.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // dd(__DIR__ . '/../bootstrap/app.php');

        $app = require __DIR__ . '/../bootstrap/app.php';

        /** @var \Pterodactyl\Console\Kernel $kernel */
        $kernel = $app->make(\ProcessMaker\Console\Kernel::class);

        $kernel->bootstrap();
        $kernel->call('migrate:fresh');
        $kernel->call('db:seed');
    }

    /**
     * Register the base URL with Dusk.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        if (!env('CLOUD_BROWSER_TESTING', false)) {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        if (!env('SAUCELABS_BROWSER_TESTING', false)) {
            $options = (new ChromeOptions)->addArguments([
                '--disable-gpu',
                '--headless',
                '--window-size=1920,1080',
            ]);

            return RemoteWebDriver::create(
                'http://localhost:9515',
                DesiredCapabilities::chrome()
                    ->setCapability(ChromeOptions::CAPABILITY, $options)
                    ->setCapability('acceptInsecureCerts', true)
            );
        } else {
            // We currently support SauceLabs based cloud testing
            return RemoteWebDriver::create(
                "https://" . env('SAUCELABS_USERNAME') . ":" . env('SAUCELABS_ACCESS_KEY') . "@ondemand.saucelabs.com:443/wd/hub",
                [
                    "platform" => env('SAUCELABS_PLATFORM', "Windows 7"),
                    "browserName" => env('SAUCELABS_BROWSER', "chrome"),
                    "version" => env('SAUCELABS_BROWSER_VERSION', "67")
                ]
            );
        }
    }
}

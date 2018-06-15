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

    public function setUp()
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        if(!env('CLOUD_BROWSER_TESTING', false)) {
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
        if(!env('SAUCELABS_BROWSER_TESTING', false)) {
            $options = (new ChromeOptions)->addArguments([
                '--disable-gpu',
                '--headless'
            ]);

            return RemoteWebDriver::create(
                'http://localhost:9515', DesiredCapabilities::chrome()->setCapability(
                    ChromeOptions::CAPABILITY, $options
                )
            );
        } else {
            // We currently support SauceLabs based cloud testing
            return RemoteWebDriver::create(
                "https://" . env('SAUCELABS_USERNAME') . ":" . env('SAUCELABS_ACCESS_KEY') . "@ondemand.saucelabs.com:443/wd/hub",
                [
                    "platform" => env('SAUCELABS_PLATFORM', "Windows 7"), 
                    "browserName" => env('SAUCELABS_BROWSER', "chrome"), 
                    "version"=> env('SAUCELABS_BROWSER_VERSION', "67")
                ]
            );
        }
    }
}

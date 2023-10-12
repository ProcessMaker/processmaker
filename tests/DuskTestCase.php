<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        static::startChromeDriver();
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        /**
         * Run in a real browser. You can tunnel through vagrant by running
         * ssh vagrant@192.168.10.10 -N -R 4444:localhost:4444
         * Then start selenium standalone server on your host machine
         * (Install it with `brew install selenium-server-standalone`)
         */
        if (env('SELENIUM_SERVER')) {
            $options = (new ChromeOptions)->addArguments([
                '--ignore-ssl-errors',
                '--window-size=1200,720',
            ]);

            return RemoteWebDriver::create(
                env('SELENIUM_SERVER'), // 'http://localhost:4444/wd/hub/',
                DesiredCapabilities::chrome()
                    ->setCapability(ChromeOptions::CAPABILITY, $options)
                    ->setCapability('acceptInsecureCerts', true)
            );

            /**
             * Run in Saucelabs. This is only use for CircleCI
             */
        } elseif (env('SAUCELABS_BROWSER_TESTING')) {
            return RemoteWebDriver::create(
                'https://' . env('SAUCELABS_USERNAME') . ':' . env('SAUCELABS_ACCESS_KEY') . '@ondemand.saucelabs.com:443/wd/hub',
                [
                    'platform' => env('SAUCELABS_PLATFORM', 'Windows 10'),
                    'browserName' => env('SAUCELABS_BROWSER', 'chrome'),
                    'version' => env('SAUCELABS_BROWSER_VERSION', '73'),
                ]
            );

            /**
             * Run with default headless mode in the vagrant machine
             */
        } else {
            $options = (new ChromeOptions)->addArguments([
                '--disable-gpu',
                '--headless',
                '--no-sandbox',
                '--ignore-ssl-errors',
                '--window-size=1200,720',
                '--whitelisted-ips=""',
            ]);

            return RemoteWebDriver::create(
                'http://localhost:9515',
                DesiredCapabilities::chrome()
                    ->setCapability(ChromeOptions::CAPABILITY, $options)
                    ->setCapability('acceptInsecureCerts', true)
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $sqlFile = sys_get_temp_dir() . '/testDB.sql';
        $arg = [
            env('DB_HOST'),
            env('DB_PORT'),
            env('DB_USERNAME'),
            env('DB_PASSWORD'),
            env('DB_DATABASE'),
            $sqlFile,
        ];

        if (!file_exists($sqlFile) || env('CLEAN_DB', false) === true) {
            $this->artisan('migrate:fresh', [
                '--seed' => true,
                '--force' => true,
            ]);
            $cmd = base_path('tests/Browser/mysql_backup.sh') . ' ' . implode(' ', $arg);
            exec($cmd, $out, $ret);
        } else {
            $cmd = base_path('tests/Browser/mysql_restore.sh') . ' ' . implode(' ', $arg) . ' 2>&1';
            exec($cmd, $out, $ret);
        }
    }
}

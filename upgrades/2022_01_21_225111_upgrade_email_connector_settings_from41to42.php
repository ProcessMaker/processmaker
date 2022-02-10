<?php

use ProcessMaker\Models\Setting;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Migrations\Migration;

class UpgradeEmailConnectorSettingsFrom41to42 extends Migration
{
    protected $from = '4.1';

    protected $to = '4.2';

    protected $required = true;

    /**
     * @var array
     */
    protected $drivers;

    /**
     * @var array
     */
    protected $encryption;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var array
     */
    protected $settings_groups;

    /**
     * @param  \ProcessMaker\Packages\Connectors\Email\EmailConfig  $config
     */
    public function __construct()
    {
        $config = new EmailConfig();

        $this->drivers = $config::drivers;
        $this->encryption = $config::encryption;
        $this->prefix = $config::prefix;
        $this->settings_groups = $config::settings_groups;
    }

    public function test()
    {
        // Test if we can run

        Artisan::printLog('Test');
    }

    /**
     * Run the migrations.
     *
     * @return void
     * @throws \Exception
     */
    public function up()
    {
        if (!$this->connectorSendEmailInstalled()) {
            logger()->error('The package connector-send-email must be installed to run this upgrade.', [
                'package' => 'connector-send-email',
                'class' => __CLASS__,
                'method' => __METHOD__
            ]);

            return;
        }

        // Clear out any cached environment variable
        Artisan::call('optimize:clear', [
            '--quiet' => true
        ]);

        // If the .env file doesn't exist, we have nothing to upgrade from
        if (!$this->envFileExists()) {
            throw new RuntimeException('Upgrade migration failed: .env file is missing');
        }

        $driver = env('MAIL_DRIVER');

        // If we don't know which driver we're taking from the
        // .env file, then we shouldn't continue since we won't
        // know where to set all of the other settings, e.g.
        // MAIL_HOST, MAIL_ENCRYPTION, etc.
        if (!$driver) {
            throw new RuntimeException('Upgrade migration failed: The MAIL_DRIVER environment variable was not found in the .env file. This is required to run the upgrade process.');
        }

        // Validate the driver and bail if it's not supported
        if (!in_array($driver, $this->drivers, true)) {
            throw new RuntimeException("Unsupported MAIL_DRIVER found in the .env file: \"$driver\"");
        }

        // First, let's find the primary mail driver setting
        $mail_driver_key = $this->prefix.'MAIL_DRIVER';
        $mail_driver_setting = Setting::byKey($mail_driver_key);

        if (!$mail_driver_setting instanceof Setting) {
            throw new RuntimeException("Setting with key \"$mail_driver_key\" was not found.");
        }

        // Now update the mail driver setting
        $mail_driver_setting->config = (string) array_search($driver, $this->drivers, true);

        // Update the setting and remove it from .env
        if ($mail_driver_setting->save()) {
            $this->removeEnvValue('MAIL_DRIVER');
        }

        // Loop through the variables for a given driver
        // and if we find the value, create a setting with
        // the variable name/value and then remove it
        // from the .env file
        foreach ($this->settings_groups[$driver] as $variable_name) {
            // We've already set this so we can skip it
            if ($variable_name === 'MAIL_DRIVER') {
                continue;
            }

            $env_value = env($variable_name);

            if (null === $env_value) {
                continue;
            }

            if ($variable_name === 'MAIL_ENCRYPTION') {
                if (!in_array($env_value, $this->encryption, true)) {
                    continue;
                }

                $env_value = (string) array_search($env_value, array_values($this->encryption), true);
            }

            $setting_key = $this->prefix.$variable_name;
            $setting = Setting::byKey($setting_key);

            if (!$setting instanceof Setting) {
                logger()->warning("Warning while running upgrade migration: Setting with key: $setting_key not found, skipping...", [
                    'migration' => __FILE__
                ]);

                continue;
            }

            $setting->config = $env_value;

            if ($setting->save()) {
                $this->removeEnvValue($variable_name);

                logger("Upgrade migration: Setting with key $setting_key successfully updated", [
                    'migration' => __FILE__
                ]);
            }
        }
    }

    protected function connectorSendEmailInstalled()
    {
        return File::exists(base_path('vendor/processmaker/connector-send-email'));
    }

    /**
     * @return bool
     */
    protected function envFileExists()
    {
        return File::exists(base_path('.env'));
    }

    /**
     * Remove any line(s) containing a set variable in .env
     *
     * @param  string  $variable_name
     *
     * @return void
     */
    protected function removeEnvValue(string $variable_name)
    {
        if (!$this->envFileExists()) {
            return;
        }

        $file_contents = '';
        $file_path = base_path('.env');
        $file_handle = fopen($file_path, 'rb');

        if (!is_resource($file_handle)) {
            return;
        }

        while (($line = fgets($file_handle)) !== false) {
            if (!Str::contains($line, $variable_name)) {
                $file_contents .= $line;
            }
        }

        fclose($file_handle);
        file_put_contents($file_path, $file_contents);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

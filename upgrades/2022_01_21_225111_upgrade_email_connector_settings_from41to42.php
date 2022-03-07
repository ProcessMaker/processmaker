<?php

use ProcessMaker\Models\Setting;
use ProcessMaker\Upgrades\UpgradeMigration;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpgradeEmailConnectorSettingsFrom41to42 extends UpgradeMigration
{
    /**
     * Prefix to comments made in the .env file
     *
     * @var string
     */
    public const COMMENT_PREFIX = '# Migrated: ';

    /**
     * The version of ProcessMaker being upgraded *to*
     *
     * @var string example: 4.2.28
     */
    public $to = '4.2.29-RC';

    /**
     * Upgrades migration cannot be skipped if the pre-upgrade checks fail
     *
     * @var bool
     */
    public $required = true;

    /**
     * @var \ProcessMaker\Packages\Connectors\Email\EmailConfig
     */
    protected $config;
    protected $driver;
    protected $setting;

    public $updated = [];

    /**
     * @param  \ProcessMaker\Packages\Connectors\Email\EmailConfig  $config
     */
    public function __construct()
    {
        $this->config = new EmailConfig();
    }

    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * There is no need to check against the version(s) as the upgrade
     * migrator will do this automatically and fail if the correct
     * version(s) are not present.
     *
     * Throw a RuntimeException if the conditions to run this upgrade migration
     * are *NOT* correct. If this is not a required upgrade, then it will be
     * skipped. Otherwise, the thrown exception will stop the remaining
     * upgrade migrations from running.
     *
     * @return void
     * @throws \Exception
     */
    public function preflightChecks()
    {
        Artisan::call('optimize:clear', [
            '--quiet' => true
        ]);

        if (!$this->connectorSendEmailInstalled()) {
            throw new RuntimeException('The package connector-send-email must be installed to run this upgrade.');
        }

        // If the .env file doesn't exist, we have nothing to upgrade from
        if (!$this->envFileAvailable()) {
            throw new RuntimeException('The .env file is missing');
        }

        // If we don't know which driver we're taking from the
        // .env file, then we shouldn't continue since we won't
        // know where to set all of the other settings, e.g.
        // MAIL_HOST, MAIL_ENCRYPTION, etc.
        if (!$this->getDriverFromEnv()) {
            throw new RuntimeException('The MAIL_DRIVER environment variable was not found in the .env file. This is required to run the upgrade process.');
        }

        // Validate the driver and bail if it's not supported
        if (!in_array($this->getDriverFromEnv(), $this->config::drivers, true)) {
            throw new RuntimeException("Unsupported MAIL_DRIVER found in the .env file: \"".$this->getDriverFromEnv()."\"");
        }

        // First, let's find the primary mail driver setting
        $setting = $this->getDriverSetting();

        // Double-check to make sure we found the right Setting and if so,
        // all of our pre-flight checks before running the upgrade
        if (!$setting instanceof Setting) {
            throw new RuntimeException("Setting with key \"".$this->config::prefix."MAIL_DRIVER\" was not found.");
        }
    }

    /**
     * Run the migrations.
     *
     * @return void
     * @throws \Exception
     */
    public function up()
    {
        $env = [];

        // Setup the new config attribute for the related Setting
        $attributes = ['config' => (string) $this->findConfigValue(
            $driver = $this->getDriverFromEnv(), $this->config::drivers
        )];

        // Fill the model's attributes with the updates one and save it
        $driver_setting = $this->getDriverSetting()->fill($attributes);

        // If it's different than what we've found the the .env file,
        // than save the setting and add it to the $env array
        if ($driver_setting->isDirty() && $driver_setting->save()) {
            $env['MAIL_DRIVER'] = $driver;
        }

        // Loop through the variables for a given driver
        // and if we find the value, create a setting with
        // the variable name/value and then remove it
        // from the .env file
        foreach ($this->config::settings_groups[$driver] as $variable_name) {

            // We've already set this so we can skip it
            if ($variable_name === 'MAIL_DRIVER') {
                continue;
            }

            $env_value = env($variable_name);

            // If we can't a value for it, then skip it
            if (blank($env_value)) {
                continue;
            }

            // Make sure we set the index number for
            // the Setting config attribute
            if ($variable_name === 'MAIL_ENCRYPTION') {
                // We want to save the actual .env value instead of
                // the index number from the encryption array
                $env[$variable_name] = $env_value;

                // Now we can set it back to the index of the
                // encryption array (as required by the way
                // the config works for Settings)
                $env_value = $this->findConfigValue(
                    $env_value, $this->config::encryption
                );

                if (blank($env_value)) {
                    continue;
                }
            }

            // Find the related setting model for this env value
            $setting = Setting::byKey($this->config::prefix.$variable_name)
                              ->fill(['config' => $env_value]);

            // Attempt to update the setting model with the new
            // value found in the .env file
            if ($variable_name !== 'MAIL_ENCRYPTION') {
                // Save the newly updated setting value
                $env[$variable_name] = $env_value;
            }

            // If there aren't any changes to make, we can move on
            if ($setting->isDirty()) {
                $setting->save();
            }
        }

        // Comment out the found environment variables
        // and include a system message + prefix which
        // will allow us to remove them during a
        // rollback if necessary
        $this->addSnapshotComments($env);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->uncommentLines($with = 'MIGRATED_');

        $this->removeSnapshotComments();
    }

    /**
     * Comment out the migrated .env variables and add meta-comments on this migration
     *
     * @param  array  $values
     *
     * @return void
     */
    protected function addSnapshotComments(array $values)
    {
        if (blank($values)) {
            return;
        }

        foreach ($values as $variable => $value) {
            $this->removeLineContaining($variable);
        }

        $this->appendComment('The comments below were created during an automated upgrade migration.');
        $this->appendComment('Please leave this message and the comments in place.');
        $this->appendComment('Run on: '.now());
        $this->appendComment('Upgrade migration: '.str_replace('.php', '', basename(__FILE__)));

        // Save the new values as commented out lines (in
        // case we need to rollback)
        foreach ($values as $variable => $value) {
            $this->appendCommentedVariable("MIGRATED_{$variable}", $value);
        }
    }

    /**
     * Remove the meta-information left by the upgrade migration
     *
     * @return void
     */
    protected function removeSnapshotComments()
    {
        foreach ($this->getAppendedComments() as $line) {
            $this->removeLineContaining($line);
        }
    }

    /**
     * @param  string  $only_with
     *
     * @return void
     */
    protected function uncommentLines(string $only_with = null)
    {
        foreach ($this->getAppendedComments($only_with) as $line) {
            $this->uncommentLine($line);
        }
    }

    /**
     * Uncomment a specific line in the .env file
     *
     * @param  string  $line
     *
     * @return void
     */
    protected function uncommentLine(string $line)
    {
        // Format what we need to re-append to the env file
        $uncommented = str_replace(self::COMMENT_PREFIX, '', $line);
        // Remove the comment
        $this->removeLineContaining($line);
        // Re-append the variable and value
        File::append(base_path('.env'), $uncommented.PHP_EOL);
    }

    /**
     * Grab all system and migration comments made in the .env file
     *
     * @param  string|array|null  $only_with
     *
     * @return array
     */
    protected function getAppendedComments($only_with = null)
    {
        return array_filter(array_map(static function ($line) use ($only_with) {

            // We only want comments we left behind previously
            if (!Str::contains($line, $prefix = self::COMMENT_PREFIX)) {
                return;
            }

            // If the $variables_only arg is passed as true, then only
            // return the commented out variables
            if ($only_with && !Str::contains($line, $only_with)) {
                return;
            }

            // Reformat so we get only the comment itself
            $needles = array_filter(
                is_array($only_with) ? array_merge([$prefix], $only_with) : [$prefix, $only_with]
            );

            return str_replace($needles, '', $line);

        }, $this->getComments()));
    }

    /**
     * Append a commented-out variable and value to the .env file
     *
     * @param  string  $variable
     * @param  string  $value
     *
     * @return void
     */
    protected function appendCommentedVariable(string $variable, string $value)
    {
        // Wrap env values containing a space with double quotes
        if (Str::contains($value, " ")) {
            $value = "\"{$value}\"";
        }

        $this->appendComment("{$variable}={$value}");
    }

    /**
     * Append a comment to the .env file
     *
     * @param  string  $line
     *
     * @return void
     */
    protected function appendComment(string $line)
    {
        if ($this->envFileAvailable()) {
            File::append(base_path('.env'), self::COMMENT_PREFIX.$line.PHP_EOL);
        }
    }

    /**
     * Get all comment lines from the .env file as an array
     *
     * @return array
     */
    protected function getComments()
    {
        if (!$this->envFileAvailable()) {
            return [];
        }

        $env = File::get(base_path('.env'));

        return collect(explode(PHP_EOL, $env))->transform(function ($line) {
            return str_replace(PHP_EOL, '', $line);
        })->filter(function ($line) {
            return Str::startsWith($line, '#');
        })->values()->toArray();
    }

    /**
     * @return bool
     */
    protected function envFileAvailable()
    {
        return File::exists($env = base_path('.env'))
            && File::isWritable($env)
            && File::isReadable($env);
    }

    /**
     * Remove any line(s) containing a set variable in .env
     *
     * @param  string  $search
     *
     * @return void
     */
    protected function removeLineContaining(string $search)
    {
        if (!$this->envFileAvailable()) {
            return;
        }

        $file_contents = '';
        $file_path = base_path('.env');
        $file_handle = fopen($file_path, 'rb');

        if (!is_resource($file_handle)) {
            return;
        }

        while (($line = fgets($file_handle)) !== false) {
            if (!Str::contains($line, $search)) {
                $file_contents .= $line;
            }
        }

        fclose($file_handle);
        file_put_contents($file_path, $file_contents);
    }

    /**
     * @return mixed|string
     */
    protected function getDriverFromEnv()
    {
        if (blank($this->driver)) {
            $this->driver = env('MAIL_DRIVER');
        }

        return $this->driver;
    }

    /**
     * @return \ProcessMaker\Models\Setting|null
     * @throws \Exception
     */
    protected function getDriverSetting()
    {
        if (!$this->setting instanceof Setting) {
            $this->setting = Setting::byKey($this->config::prefix.'MAIL_DRIVER');
        }

        return $this->setting;
    }

    /**
     * @return bool
     */
    protected function connectorSendEmailInstalled()
    {
        return File::exists(base_path('vendor/processmaker/connector-send-email'));
    }

    /**
     * Find the value of an EmailConfig value
     *
     * @param $key
     * @param $array
     *
     * @return false|int|string
     */
    protected function findConfigValue($key, $array)
    {
        return (string) array_search($key, array_values($array), false);
    }
}

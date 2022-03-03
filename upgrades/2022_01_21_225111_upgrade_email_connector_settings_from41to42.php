<?php

use ProcessMaker\Models\Setting;
use ProcessMaker\Upgrades\UpgradeMigration;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpgradeEmailConnectorSettingsFrom41to42 extends UpgradeMigration
{
    public const SYSTEM_COMMENT_PREFIX = 'System:';
    public const VARIABLE_COMMENT_PREFIX = 'Migrated:';

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
     * @var string
     */
    protected $driver;

    /**
     * @var \ProcessMaker\Models\Setting|null
     */
    protected $setting;

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
     * @var string
     */
    protected $settings_group;

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
        $this->settings_group = $config::settings_group;
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
        if (!in_array($this->getDriverFromEnv(), $this->drivers, true)) {
            throw new RuntimeException("Unsupported MAIL_DRIVER found in the .env file: \"".$this->getDriverFromEnv()."\"");
        }

        // First, let's find the primary mail driver setting
        $this->getDriverSetting();

        // Double-check to make sure we found the right Setting and if so,
        // all of our pre-flight checks before running the upgrade
        if (!$this->getDriverSetting() instanceof Setting) {
            throw new RuntimeException("Setting with key \"{$this->prefix}.'MAIL_DRIVER'\" was not found.");
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
        // Save all of the removed env variable names/values
        $env = [];

        // Setup the new config attribute for the related Setting
        $attributes = ['config' => (string) array_search(
            $driver = $this->getDriverFromEnv(), $this->drivers
        )];

        // Fill the model's attributes with the
        // updates one and save it
        if ($this->getDriverSetting()->fill($attributes)->save()) {
            $env['MAIL_DRIVER'] = $driver;
        }

        // Loop through the variables for a given driver
        // and if we find the value, create a setting with
        // the variable name/value and then remove it
        // from the .env file
        foreach ($this->settings_groups[$this->getDriverFromEnv()] as $variable_name) {

            // We've already set this so we can skip it
            if ($variable_name === 'MAIL_DRIVER') {
                continue;
            }

            $env_value = env($variable_name);

            // If we can't a value for it, then skip it
            if (null === $env_value) {
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
                $env_value = (string) array_search($env_value, array_values($this->encryption));
            }

            $setting = Setting::byKey($this->prefix.$variable_name);

            if (!$setting instanceof Setting) {
                continue;
            }

            $attributes = ['config' => $env_value];

            if ($setting->fill($attributes)->save()) {
                if ($variable_name !== 'MAIL_ENCRYPTION') {
                    $env[$variable_name] = $env_value;
                }
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
        $this->removeSnapshotComments();

        $this->uncommentLines();
    }

    /**
     * @param  array  $env
     *
     * @return void
     */
    protected function addSnapshotComments(array $env)
    {
        if (blank($env)) {
            return;
        }

        foreach ($env as $variable => $value) {
            $this->removeLineContaining($variable);
        }

        $this->appendSystemComment('The comments below were created during an automated upgrade migration.');
        $this->appendSystemComment('Please leave this message and the comments in place.');
        $this->appendSystemComment('Run on: '.now());
        $this->appendSystemComment('Upgrade migration: '.str_replace('.php', '', basename(__FILE__)));

        foreach ($env as $variable => $value) {
            // Wrap env values containing a space with double quotes
            if (Str::contains($value, " ")) {
                $value = "\"{$value}\"";
            }

            $this->appendMigrationComment("{$variable}={$value}");
        }
    }

    /**
     * Remove the meta-information left by the upgrade migration
     *
     * @return void
     */
    protected function removeSnapshotComments()
    {
        foreach ($this->getAppendedComments([self::SYSTEM_COMMENT_PREFIX]) as $line) {
            $this->removeLineContaining($line);
        }
    }

    /**
     * @return void
     */
    protected function uncommentLines()
    {
        foreach ($this->getAppendedComments([$prefix = self::VARIABLE_COMMENT_PREFIX]) as $line) {
            // Format what we need to re-append to the env file
            $uncommented = str_replace("# {$prefix}: ", '', $line);

            // Remove the comment
            $this->removeLineContaining($line);

            // Re-append the variable and value
            File::append(base_path('.env'), "#{$uncommented}".PHP_EOL);
        }
    }

    /**
     * Grab all system and migration comments made in the .env file
     *
     * @param  array  $prefixes
     *
     * @return array
     */
    protected function getAppendedComments(array $prefixes = [])
    {
        $filtered = [];

        if (blank($prefixes)) {
            $prefixes = [
                self::SYSTEM_COMMENT_PREFIX,
                self::VARIABLE_COMMENT_PREFIX
            ];
        }

        foreach ($this->getComments() as $line) {
            foreach ($prefixes as $prefix) {

                // We only want comments with one of the pre-defined prefixes
                if (!Str::contains($line, $prefix)) {
                    continue;
                }

                // This is to remove the actual prefix from the comment
                // and then used to create a multi-dimensional array
                // to group the different types of comments in
                $comment = str_replace("# {$prefix}: ", '', $line);

                // Reformat the prefix so we can key our array with it
                $key = strtolower(
                    str_replace(['#', " ", ':'], '', $prefix)
                );

                // Add an empty array if one doesn't exist for the key
                if (!array_key_exists($key, $filtered)) {
                    $filtered[$key] = [];
                }

                // Finally, add the comment into the keyed array
                $filtered[$key][] = $comment;
            }
        }

        return $filtered;
    }

    /**
     * @param  string  $line
     *
     * @return void
     */
    protected function appendMigrationComment(string $line)
    {
        $this->appendComment(' '.self::VARIABLE_COMMENT_PREFIX.' '.$line);
    }

    /**
     * @param  string  $line
     *
     * @return void
     */
    protected function appendSystemComment(string $line)
    {
        $this->appendComment(' '.self::SYSTEM_COMMENT_PREFIX.' '.$line);
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
            File::append(base_path('.env'), "#{$line}".PHP_EOL);
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
        return File::exists($env = base_path('.env')) && File::isWritable($env);
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
    public function getDriverFromEnv()
    {
        if (blank($this->driver)) {
            $this->driver = env('MAIL_DRIVER');
        }

        return $this->driver;
    }

    /**
     * @param  string  $key
     *
     * @return \ProcessMaker\Models\Setting|null
     * @throws \Exception
     */
    public function getDriverSetting()
    {
        if (!$this->setting instanceof Setting) {
            $this->setting = Setting::byKey($this->prefix.'MAIL_DRIVER');
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
}

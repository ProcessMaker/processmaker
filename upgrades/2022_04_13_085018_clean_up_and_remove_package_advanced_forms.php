<?php

use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Models\ScreenType;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;
use Illuminate\Support\Facades\File;

class CleanUpAndRemovePackageAdvancedForms extends Upgrade
{
    /**
     * The version of ProcessMaker being upgraded *to*
     *
     * @var string example: 4.2.28
     */
    public $to = '4.2.30-RC1';

    /**
     * Upgrade migration cannot be skipped if the pre-upgrade checks fail
     *
     * @var bool
     */
    public $required = false;

    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * There is no need to check against the version(s) as the upgrade
     * migrator will do this automatically and fail if the correct
     * version(s) are not present.
     *
     * Throw a \RuntimeException if the conditions are *NOT* correct for this
     * upgrade migration to run. If this is not a required upgrade, then it
     * will be skipped. Otherwise the exception thrown will be caught, noted,
     * and will prevent the remaining migrations from continuing to run.
     *
     * Returning void or null denotes the checks were successful.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function preflightChecks()
    {
        if (!$this->packageAdvancedFormsInstalled()) {
            throw new RuntimeException('This upgrade migration requires package-advancedforms to be installed.');
        }

        if ($this->advancedTypeScreensExist()) {
            throw new RuntimeException('There are screens found with the type "ADVANCED". Please update or remove these screens and re-run this upgrade migration.');
        }
    }

    /**
     * Run the upgrade migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->composerRemovePackageAdvancedForms();

        $this->removeAdvancedScreenType();

        $this->removePublishedAssets();
    }

    /**
     * Reverse the upgrade migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->installPackageAdvancedForms();
    }

    /**
     * Install package-advancedforms with composer then run the package install command
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function installPackageAdvancedForms(): void
    {
        if (!is_string(system($command = 'composer require processmaker/package-advancedforms --no-interaction > /dev/null 2>&1'))) {
            throw new RuntimeException("Unknown error while running: {$command}");
        }

        if (!is_string(system($command = PHP_BINARY.' '. base_path('artisan') .' package-advancedforms:install > /dev/null 2>&1'))) {
            throw new RuntimeException("Unknown error while running: {$command}");
        }
    }

    /**
     * Remove package-advancedforms using composer
     *
     * @return void
     */
    protected function composerRemovePackageAdvancedForms(): void
    {
        system('composer remove processmaker/package-advancedforms --no-interaction');
    }

    /**
     * Remove the ScreenType: "ADVANCED"
     *
     * @return void
     */
    protected function removeAdvancedScreenType(): void
    {
        ScreenType::query()->where('name', 'ADVANCED')->delete();
    }

    /**
     * Removes the published front-end assets for package-advancedforms
     *
     * @return void
     */
    protected function removePublishedAssets(): void
    {
        if (File::exists($path = public_path('vendor/processmaker/packages/package-advancedforms'))) {
            File::deleteDirectory($path);
        }
    }

    /**
     * Are there Screens with the type "ADVANCED"?
     *
     * @return bool
     */
    protected function advancedTypeScreensExist(): bool
    {
        return ScreenVersion::query()->where('type', 'ADVANCED')->exists()
            || Screen::query()->where('type', 'ADVANCED')->exists();
    }

    /**
     * Is package-advancedforms installed
     *
     * @return bool
     */
    protected function packageAdvancedFormsInstalled(): bool
    {
        return File::exists(base_path('vendor/processmaker/package-advancedforms'));
    }
}


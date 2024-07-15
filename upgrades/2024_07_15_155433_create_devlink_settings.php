<?php

use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;

class CreateDevlinkSettings extends Upgrade
{
    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
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
        //
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        // Create category Devlink
        $groupName = 'DevLink';
        $menu = SettingsMenus::firstOrCreate([
            'menu_group' => 'devlink'
        ], [
            'menu_group_order' => 5,
            'ui' => json_encode(["icon" => 'server']),
        ]);
        // GITHUB_REPO
        Setting::firstOrCreate(['key' => "GITHUB_REPO"], [
            'format' => 'text',
            'config' => config('devlink.repo'),
            'name' => 'GITHUB Repository',
            'helper' => 'Setup the repository name',
            'group' => $groupName,
            'group_id' => $menu->getKey(),
            'hidden' => false,
            'ui' => [],
        ]);
        // GITHUB_ACCOUNT
        Setting::firstOrCreate(['key' => "GITHUB_ACCOUNT"], [
            'format' => 'text',
            'config' => config('devlink.account'),
            'name' => 'GITHUB Account',
            'helper' => 'Setup the account name',
            'group' => $groupName,
            'group_id' => $menu->getKey(),
            'hidden' => false,
            'ui' => [],
        ]);
        // GITHUB_TOKEN
        Setting::firstOrCreate(['key' => "GITHUB_TOKEN"], [
            'format' => 'text',
            'config' => config('devlink.token'),
            'name' => 'GITHUB Token',
            'helper' => 'Setup the token',
            'group' => $groupName,
            'group_id' => $menu->getKey(),
            'hidden' => false,
            'ui' => [
                'sensitive' => true,
            ],
        ]);
        // GITHUB_BRANCH
        Setting::firstOrCreate(['key' => "GITHUB_BRANCH"], [
            'format' => 'text',
            'config' => config('devlink.branch'),
            'name' => 'GITHUB Branch',
            'helper' => 'Setup the branch name',
            'group' => $groupName,
            'group_id' => $menu->getKey(),
            'hidden' => false,
            'ui' => [],
        ]);
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

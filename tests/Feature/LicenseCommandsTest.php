<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\LicensedPackageManifest;
use Tests\TestCase;

/**
 * Test license commands
 *
 * @group license
 */
class LicenseCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        // remove the license.json file if it exists
        if (Storage::disk('local')->exists('license.json')) {
            Storage::disk('local')->delete('license.json');
        }
        Cache::forget(LicensedPackageManifest::EXPIRE_CACHE_KEY);
        // run package:discover
        $this->artisan('package:discover');

        parent::tearDown();
    }

    public function testLicenseUpdateFromLocalPath()
    {
        // Create a sample license file for testing.
        $sampleLicense = '{"expires_at": "2023-12-31", "packages": ["package-translations", "package-projects"]}';
        $licenseFilePath = tempnam(sys_get_temp_dir(), 'license_');
        file_put_contents($licenseFilePath, $sampleLicense);

        $this->artisan('processmaker:license-update', ['licenseFile' => $licenseFilePath])
            ->assertExitCode(0);

        $this->assertTrue(Storage::disk('local')->exists('license.json'));
    }

    public function testLicenseUpdateWithInvalidContent()
    {
        $invalidLicense = '"invalid": "data"';
        $licenseFilePath = tempnam(sys_get_temp_dir(), 'license_');
        file_put_contents($licenseFilePath, $invalidLicense);

        $this->artisan('processmaker:license-update', ['licenseFile' => $licenseFilePath])
            ->expectsOutput('The provided license does not have a valid format.')
            ->assertExitCode(1);
    }

    public function testLicenseRemoveConfirmation()
    {
        Storage::disk('local')->put('license.json', 'sample content');

        $this->artisan('processmaker:license-remove')
            ->expectsQuestion('Are you sure you want to remove the license.json file?', false)
            ->expectsOutput('Operation cancelled. license.json was not removed.')
            ->assertExitCode(0);

        $this->assertTrue(Storage::disk('local')->exists('license.json'));
    }

    public function testLicenseRemove()
    {
        Storage::disk('local')->put('license.json', '{"expires_at": "2023-12-31", "packages": []}');

        $this->artisan('processmaker:license-remove')
            ->expectsQuestion('Are you sure you want to remove the license.json file?', true)
            ->expectsOutput('license.json removed successfully!')
            ->assertExitCode(0);

        $this->assertFalse(Storage::disk('local')->exists('license.json'));
    }

    public function testLicenseRemoveNonExistent()
    {
        $this->artisan('processmaker:license-remove')
            ->expectsOutput('license.json does not exist on the local disk.')
            ->assertExitCode(0);
    }
}

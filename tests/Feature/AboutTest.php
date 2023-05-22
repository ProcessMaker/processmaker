<?php

namespace Tests\Feature;

use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class AboutTest extends TestCase
{
    use RequestHelper;

    protected function tearDown(): void
    {
        if (file_exists($composer_json_file_backup = base_path('composer.json.bak'))) {
            rename($composer_json_file_backup, base_path('composer.json'));
        }

        parent::tearDown();
    }

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testIndexRoute()
    {
        // user without any permissions
        $this->user = User::factory()->create();

        // get the URL
        $response = $this->webCall('GET', '/about');

        // check the correct view is called
        $response->assertStatus(200);
        $response->assertViewIs('about.index');

        // Make sure we can find composer.json
        $composer_json_file = base_path('composer.json');
        $this->assertFileExists($composer_json_file);

        // Grab the composer JSON and decode it
        $composer_json = json_decode(file_get_contents($composer_json_file));

        // Test and make sure our custom property is present
        // and contains a string value
        $this->assertIsObject($composer_json);
        $this->assertObjectHasAttribute('extra', $composer_json, 'The composer.json file is missing the "extra" attribute.');
        $this->assertObjectHasAttribute('processmaker', $composer_json->extra, 'The composer.json file is missing the "extra->processmaker" attribute.');
        $this->assertObjectHasAttribute('build', $composer_json->extra->processmaker, 'The composer.json file is missing the "extra->processmaker->build" attribute.');
        $this->assertIsString($composer_json->extra->processmaker->build, 'The composer.json file "extra->processmaker->build" attribute is not a string.');
        $this->assertNotEmpty($composer_json->extra->processmaker->build, 'The composer.json file "extra->processmaker->build" attribute is empty.');

        // Test the commit hash it reads from composer.json
        $response->assertSeeText('Build #');

        // Copy composer.json over to a new backup file
        $composer_json_file_backup = base_path('composer.json.bak');
        copy($composer_json_file, $composer_json_file_backup);
        $this->assertFileExists($composer_json_file_backup);

        // Remove the extra->processmaker property so we can
        // test the about page when the extra->processmaker->build
        // property isn't present.
        unset($composer_json->extra->processmaker);
        file_put_contents($composer_json_file, json_encode($composer_json));

        // New composer.json content
        $composer_json = json_decode(file_get_contents(base_path('composer.json')));

        // Make sure we removed it
        $this->assertIsObject($composer_json);
        $this->assertObjectNotHasAttribute('processmaker', $composer_json->extra);

        // Call the about page again
        $response = $this->webCall('GET', '/about');

        // Check that the "Build #" isn't present
        // and no exceptions are thrown
        $response->assertStatus(200);
        $response->assertViewIs('about.index');
        $response->assertDontSeeText('Build #');
    }
}

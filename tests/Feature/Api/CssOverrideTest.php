<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Facades\Hash;

class CssOverrideTest extends TestCase
{

    use RequestHelper;

    private $testColor = '#1f1f1f';
    private $originalColors = '';
    private $originalAppCss = '';
    private $originalSidebarCss = '';
    private $originalQueueCss = '';

    /**
     * Verifies that the bootstrap styles can be modified
     */
    public function testCssOverride()
    {
        $this->markTestSkipped();
        chdir(app()->basePath());

        // backup of original colors
        $this->originalColors = file_get_contents("resources/sass/_colors.scss");
        $this->originalAppCss = file_get_contents("public/css/app.css");
        $this->originalSidebarCss = file_get_contents("public/css/sidebar.css");
        $this->originalQueueCss = file_get_contents("public/css/admin/queues.css");

        $response = $this->actingAs($this->user, 'api')
            ->call('POST', '/api/1.0/css_settings', $this->cssValues($this->testColor));

        // Validate that the operation was successful
        $response->assertStatus(201);

        // Validate that the style was set in the compiled css:
        $file = file_get_contents("public/css/app.css");

        $this->assertStringContainsString($this->testColor, $file);

    }


    /**
     * Verifies that the bootstrap styles are validated
     */
    public function testEmptyParameters()
    {
        $response = $this->actingAs($this->user, 'api')->call('POST', '/api/1.0/css_settings', []);

        //Validate that the error does a redirection
        $response->assertStatus(302);
    }

    /**
     * Verifies that the bootstrap styles are validated
     */
    public function testWrongKeys()
    {
        $response = $this->actingAs($this->user, 'api')->call('POST', '/api/1.0/css_settings', ['wrongkey' => 'key']);

        //Validate that the error does a redirection
        $response->assertStatus(302);
    }

    public function tearDown()
    {
        if ($this->getName() === 'testCssOverride')
        {
            //restore original colors
            file_put_contents('resources/sass/_colors.scss', $this->originalColors);
            file_put_contents('public/css/app.css', $this->originalAppCss);
            file_put_contents('public/css/sidebar.css', $this->originalSidebarCss );
            file_put_contents('public/css/admin/queues.css', $this->originalQueueCss);
        }
        parent::tearDown();
    }

    private function cssValues($testColor)
    {
        return [
           'key'  => 'css-override',
            'variables' => json_encode([
                [
                    'id' => '$primary',
                    'value' => $testColor,
                    'title' => 'Primary'
                ],
                [
                    'id' => '$secondary',
                    'value' => '#788793',
                    'title' => 'Secondary'
                ],
                [
                    'id' => '$success',
                    'value' => '#00bf9c',
                    'title' => 'Success'
                ],
                [
                    'id' => '$info',
                    'value' => '#17a2b8',
                    'title' => 'Info'
                ],
                [
                    'id' => '$warning',
                    'value' => '#fbbe02',
                    'title' => 'Warning'
                ],
                [
                    'id' => '$danger',
                    'value' => '#ed4757',
                    'title' => 'Danger'
                ],
                [
                    'id' => '$light',
                    'value' => '#ffffff',
                    'title' => 'Light'
                ]
            ])
        ];
    }
}

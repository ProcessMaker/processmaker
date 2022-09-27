<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CssOverrideTest extends TestCase
{
    use RequestHelper;

    private $testColor = '#1f1f1f';

    private $originalColors = '';

    private $originalAppCss = '';

    private $originalSidebarCss = '';

    private $originalQueueCss = '';

    /**
     * Verifies that the bootstrap styles are validated
     */
    public function testEmptyParameters()
    {
        $response = $this->actingAs($this->user, 'api')->call('POST', '/api/1.0/customize-ui', []);

        $response->assertStatus(422);
    }

    /**
     * Verifies that the bootstrap styles are validated
     */
    public function testWrongKeys()
    {
        $response = $this->actingAs($this->user, 'api')->call('POST', '/api/1.0/customize-ui', ['wrongkey' => 'key']);

        $response->assertStatus(422);
    }

    /**
     * Verifies that the reset css works
     */
    public function testResetCss()
    {
        // $this->markTestSkipped('FOUR-6653');

        $data = $this->cssValues('#ff0000');
        $data['reset'] = true;
        $response = $this->actingAs($this->user, 'api')->call('POST', '/api/1.0/customize-ui', $data);
        $response->assertStatus(201);
    }

    private function cssValues($testColor)
    {
        return [
            'key' => 'css-override',
            'variables' => json_encode([
                [
                    'id' => '$primary',
                    'value' => $testColor,
                    'title' => 'Primary',
                ],
                [
                    'id' => '$secondary',
                    'value' => '#788793',
                    'title' => 'Secondary',
                ],
                [
                    'id' => '$success',
                    'value' => '#00bf9c',
                    'title' => 'Success',
                ],
                [
                    'id' => '$info',
                    'value' => '#17a2b8',
                    'title' => 'Info',
                ],
                [
                    'id' => '$warning',
                    'value' => '#fbbe02',
                    'title' => 'Warning',
                ],
                [
                    'id' => '$danger',
                    'value' => '#ed4757',
                    'title' => 'Danger',
                ],
                [
                    'id' => '$light',
                    'value' => '#ffffff',
                    'title' => 'Light',
                ],
            ]),
        ];
    }
}

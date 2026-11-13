<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Screen;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DeployScreenControllerTest extends TestCase
{
    use RequestHelper;

    private const API_ENDPOINT = '/package-skeleton/deploy-screen';

    private const GET_ENDPOINT = '/package-skeleton/screen-yaml';

    public function testDeployScreenRequiresAuthentication(): void
    {
        $this->postJson('/api/1.0' . self::API_ENDPOINT, [
            'yml_content' => "config: []\n",
            'name' => 'sample-screen',
        ])->assertStatus(401);
    }

    public function testDeployScreenCreatesFromYaml(): void
    {
        $yaml = <<<'YAML'
config:
  - component: FormBlank
    items: []
computed: []
watchers: []
custom_css: ''
YAML;

        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => $yaml,
            'name' => 'sample-screen',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'created',
                'name' => 'sample-screen',
            ]);

        $this->assertNotNull(Screen::where('title', 'sample-screen')->first());
    }

    public function testGetScreenYamlReturnsYamlForPublishedScreen(): void
    {
        $yaml = "config: []\n";
        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => $yaml,
            'name' => 'yaml-pull-screen',
        ])->assertOk();

        $response = $this->actingAs($this->user, 'api')
            ->get('/api/1.0' . self::GET_ENDPOINT . '?name=yaml-pull-screen');

        $response->assertOk();
        $this->assertStringContainsString('yaml', (string) $response->headers->get('Content-Type'));
    }
}

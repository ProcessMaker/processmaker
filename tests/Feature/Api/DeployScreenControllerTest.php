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

    public function testDeployScreenPersistsPreviewPresets(): void
    {
        $yaml = <<<'YAML'
config: []
computed: []
watchers: []
custom_css: ''
preview_presets:
  - title: Empty / start
    data: '{}'
  - title: Happy path
    data:
      callsign: AVA123
YAML;

        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => $yaml,
            'name' => 'preset-screen',
        ])->assertOk();

        $screen = Screen::where('title', 'preset-screen')->first();
        $expected = [
            ['title' => 'Empty / start', 'data' => '{}'],
            ['title' => 'Happy path', 'data' => '{"callsign":"AVA123"}'],
        ];
        $this->assertSame($expected, $screen->preview_presets);
        $this->assertSame($expected, $screen->versions()->published()->first()->preview_presets);

        $updated = <<<'YAML'
config: []
preview_presets:
  - title: Updated
    data: '{"callsign":"N45XB"}'
YAML;
        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => $updated,
            'name' => 'preset-screen',
        ])->assertOk()->assertJson(['action' => 'updated']);

        $screen->refresh();
        $this->assertSame(
            [['title' => 'Updated', 'data' => '{"callsign":"N45XB"}']],
            $screen->preview_presets
        );

        $pull = $this->actingAs($this->user, 'api')
            ->get('/api/1.0' . self::GET_ENDPOINT . '?name=preset-screen');
        $pull->assertOk();
        $this->assertStringContainsString('Updated', $pull->getContent());
        $this->assertStringContainsString('N45XB', $pull->getContent());
    }
}

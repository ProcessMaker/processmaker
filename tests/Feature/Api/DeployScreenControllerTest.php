<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Package\Versions\Models\VersionHistory;
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

    public function testDeployScreenUpdateRequiresVersionName(): void
    {
        $name = 'version-name-required-screen';
        $yaml = "config: []\n";

        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => $yaml,
            'name' => $name,
        ])->assertOk();

        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => "config: []\ncomputed: []\n",
            'name' => $name,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['version_name']);
    }

    public function testDeployScreenUpdateStoresVersionName(): void
    {
        if (!class_exists(VersionHistory::class)) {
            $this->markTestSkipped('package-versions is not installed.');
        }

        $name = 'version-name-stored-screen';
        $summary = 'Add computed properties';

        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => "config: []\n",
            'name' => $name,
        ])->assertOk();

        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => "config: []\ncomputed: []\n",
            'name' => $name,
            'version_name' => $summary,
        ])->assertOk();

        $screen = Screen::where('title', $name)->firstOrFail();
        $version = $screen->versions()->orderByDesc('id')->first();
        $this->assertInstanceOf(ScreenVersion::class, $version);

        $history = VersionHistory::query()
            ->where('versionable_id', $version->id)
            ->where('versionable_type', ScreenVersion::class)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame($summary, $history->subject);
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

    public function testGetScreenYamlAddsEmptyInspectorToEveryItem(): void
    {
        $yaml = <<<'YAML'
config:
  - name: Page 1
    items:
      - component: FormInput
        inspector: []
        config:
          name: first
      - component: FormMultiColumn
        inspector: []
        container: true
        items:
          - - component: FormInput
              inspector: []
              config:
                name: nested
          - []
YAML;
        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => $yaml,
            'name' => 'inspector-pull-screen',
        ])->assertOk();

        $pull = $this->actingAs($this->user, 'api')
            ->get('/api/1.0' . self::GET_ENDPOINT . '?name=inspector-pull-screen');

        $pull->assertOk();
        $parsed = \Symfony\Component\Yaml\Yaml::parse($pull->getContent());
        $items = $parsed['config'][0]['items'];
        $this->assertSame([], $items[0]['inspector']);
        $this->assertSame([], $items[1]['inspector']);
        $this->assertSame([], $items[1]['items'][0][0]['inspector']);
        $this->assertSame([], $items[1]['items'][1]);
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
            'version_name' => 'Update preview presets',
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

    public function testDeployScreenEncodesEmptyYamlMappingsAsObjects(): void
    {
        $yaml = <<<'YAML'
config: []
preview_presets:
  - title: Empty mapping
    data: {}
  - title: Nested empty mapping
    data:
      callsign: AVA123
      meta: {}
YAML;

        $this->apiCall('POST', self::API_ENDPOINT, [
            'yml_content' => $yaml,
            'name' => 'preset-empty-maps',
        ])->assertOk();

        $screen = Screen::where('title', 'preset-empty-maps')->first();
        $this->assertSame(
            [
                ['title' => 'Empty mapping', 'data' => '{}'],
                ['title' => 'Nested empty mapping', 'data' => '{"callsign":"AVA123","meta":{}}'],
            ],
            $screen->preview_presets
        );
    }
}

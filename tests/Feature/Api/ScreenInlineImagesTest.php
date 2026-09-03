<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\Screen;
use ProcessMaker\Screens\ScreenInlineImageNormalizer;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ScreenInlineImagesTest extends TestCase
{
    use RequestHelper;

    private const TINY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUpStorageFake(): void
    {
        Storage::fake(config('media-library.disk_name'));
    }

    public function testDraftConvertsInlineImagesAndReturnsConfig()
    {
        $screen = Screen::factory()->create(['type' => 'FORM']);
        $dataUri = 'data:image/png;base64,' . self::TINY_PNG;
        $config = [[
            'name' => 'Default',
            'items' => [[
                'component' => 'FormHtmlViewer',
                'config' => [
                    'content' => '<img alt="x" src="' . $dataUri . '" />',
                ],
            ]],
        ]];

        $response = $this->apiCall('PUT', "/screens/{$screen->id}/draft", [
            'title' => $screen->title,
            'description' => $screen->description,
            'type' => $screen->type,
            'screen_category_id' => $screen->screen_category_id,
            'config' => $config,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('converted_images', 1);
        $this->assertStringNotContainsString(
            'base64,',
            $response->json('config.0.items.0.config.content')
        );

        $draft = $screen->getDraftVersion();
        $this->assertNotNull($draft);
        $this->assertStringNotContainsString('base64,', $draft->config[0]['items'][0]['config']['content']);
        $this->assertSame(1, $screen->fresh()->getMedia(ScreenInlineImageNormalizer::COLLECTION)->count());
    }

    public function testDraftWithoutInlineImagesKeepsNoContentResponse()
    {
        $screen = Screen::factory()->create(['type' => 'FORM']);

        $response = $this->apiCall('PUT', "/screens/{$screen->id}/draft", [
            'title' => $screen->title,
            'description' => $screen->description,
            'type' => $screen->type,
            'screen_category_id' => $screen->screen_category_id,
            'config' => [['name' => 'Default', 'items' => []]],
        ]);

        $response->assertStatus(204);
    }
}

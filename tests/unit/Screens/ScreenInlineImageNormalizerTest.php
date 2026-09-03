<?php

namespace Tests\Unit\Screens;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\Screen;
use ProcessMaker\Screens\ScreenInlineImageNormalizer;
use Tests\TestCase;

class ScreenInlineImageNormalizerTest extends TestCase
{
    private const TINY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUpStorageFake(): void
    {
        Storage::fake(config('media-library.disk_name'));
    }

    public function testReplacesBase64InRichTextContent()
    {
        $screen = Screen::factory()->create();
        $dataUri = 'data:image/png;base64,' . self::TINY_PNG;
        $config = [[
            'name' => 'Default',
            'items' => [[
                'component' => 'FormHtmlViewer',
                'config' => [
                    'content' => '<p>Hello</p><img src="' . $dataUri . '" alt="logo" />',
                ],
            ]],
        ]];

        $result = app(ScreenInlineImageNormalizer::class)->normalize($screen, $config);

        $this->assertTrue($result->wasModified());
        $this->assertSame(1, $result->convertedCount());
        $content = $result->config()[0]['items'][0]['config']['content'];
        $this->assertStringNotContainsString('data:image/png;base64,', $content);
        $this->assertStringContainsString('<img src="', $content);
        $this->assertSame(1, $screen->getMedia(ScreenInlineImageNormalizer::COLLECTION)->count());
    }

    public function testDeduplicatesRepeatedBase64InRichText()
    {
        $screen = Screen::factory()->create();
        $dataUri = 'data:image/png;base64,' . self::TINY_PNG;
        $config = [[
            'name' => 'Default',
            'items' => [[
                'component' => 'FormHtmlViewer',
                'config' => [
                    'content' => '<img src="' . $dataUri . '" alt="logo" />',
                ],
            ]],
        ]];

        $normalizer = app(ScreenInlineImageNormalizer::class);
        $first = $normalizer->normalize($screen, $config);
        $second = $normalizer->normalize($screen, $config);

        $this->assertSame(1, $first->convertedCount());
        $this->assertSame(0, $second->convertedCount());
        $this->assertTrue($second->wasModified());
        $this->assertSame(1, $screen->getMedia(ScreenInlineImageNormalizer::COLLECTION)->count());
        $this->assertSame(
            $first->config()[0]['items'][0]['config']['content'],
            $second->config()[0]['items'][0]['config']['content']
        );
    }

    public function testWalksMultiColumnNestedItems()
    {
        $screen = Screen::factory()->create();
        $dataUri = 'data:image/png;base64,' . self::TINY_PNG;
        $config = [[
            'name' => 'Default',
            'items' => [[
                'component' => 'FormMultiColumn',
                'items' => [[
                    [
                        'component' => 'FormHtmlViewer',
                        'config' => [
                            'content' => '<img src="' . $dataUri . '" />',
                        ],
                    ],
                ]],
            ]],
        ]];

        $result = app(ScreenInlineImageNormalizer::class)->normalize($screen, $config);

        $this->assertSame(1, $result->convertedCount());
        $content = $result->config()[0]['items'][0]['items'][0][0]['config']['content'];
        $this->assertStringNotContainsString('base64,', $content);
    }
}

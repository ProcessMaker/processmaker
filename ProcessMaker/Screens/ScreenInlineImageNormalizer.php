<?php

namespace ProcessMaker\Screens;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Screen;
use Throwable;

class ScreenInlineImageNormalizer
{
    public const COLLECTION = 'inline_images';

    public const CONTENT_HASH_PROPERTY = 'content_hash';

    private const DATA_IMAGE_PATTERN = '/data:image\/([a-zA-Z0-9+.-]+);base64,([A-Za-z0-9+\/=]+)/';

    private int $convertedCount = 0;

    private int $replacedCount = 0;

    public function normalize(Screen $screen, array $config): ScreenInlineImageNormalizationResult
    {
        $this->convertedCount = 0;
        $this->replacedCount = 0;
        $normalized = $this->walkPages($screen, $config);

        return new ScreenInlineImageNormalizationResult(
            $normalized,
            $this->convertedCount,
            $this->replacedCount
        );
    }

    public function configContainsInlineImages(array $config): bool
    {
        return $this->containsInlineImagesInPages($config);
    }

    private function containsInlineImagesInPages(array $config): bool
    {
        foreach ($config as $page) {
            if (!is_array($page) || empty($page['items']) || !is_array($page['items'])) {
                continue;
            }
            if ($this->containsInlineImagesInItems($page['items'])) {
                return true;
            }
        }

        return false;
    }

    private function containsInlineImagesInItems(array $items): bool
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $component = Arr::get($item, 'component');
            if ($component === 'FormMultiColumn') {
                if ($this->containsInlineImagesInMultiColumn(Arr::get($item, 'items', []))) {
                    return true;
                }
                continue;
            }

            if (!empty($item['items']) && is_array($item['items'])) {
                if ($this->containsInlineImagesInItems($item['items'])) {
                    return true;
                }
            }

            if ($component === 'FormHtmlViewer' && $this->contentHasInlineImage(Arr::get($item, 'config.content'))) {
                return true;
            }
        }

        return false;
    }

    private function containsInlineImagesInMultiColumn(array $columns): bool
    {
        foreach ($columns as $columnItems) {
            if (!is_array($columnItems)) {
                continue;
            }
            if ($this->containsInlineImagesInItems($columnItems)) {
                return true;
            }
        }

        return false;
    }

    private function contentHasInlineImage(mixed $content): bool
    {
        return is_string($content)
            && $content !== ''
            && (bool) preg_match(self::DATA_IMAGE_PATTERN, $content);
    }

    private function walkPages(Screen $screen, array $config): array
    {
        foreach ($config as $pageIndex => $page) {
            if (!is_array($page)) {
                continue;
            }
            if (!empty($page['items']) && is_array($page['items'])) {
                $config[$pageIndex]['items'] = $this->walkItems($screen, $page['items']);
            }
        }

        return $config;
    }

    private function walkItems(Screen $screen, array $items): array
    {
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $component = Arr::get($item, 'component');
            if ($component === 'FormMultiColumn') {
                $items[$index]['items'] = $this->walkMultiColumn($screen, Arr::get($item, 'items', []));
                continue;
            }

            if (!empty($item['items']) && is_array($item['items'])) {
                $items[$index]['items'] = $this->walkItems($screen, $item['items']);
            }

            $items[$index] = $this->normalizeItem($screen, $items[$index]);
        }

        return $items;
    }

    private function walkMultiColumn(Screen $screen, array $columns): array
    {
        foreach ($columns as $columnIndex => $columnItems) {
            if (!is_array($columnItems)) {
                continue;
            }
            $columns[$columnIndex] = $this->walkItems($screen, $columnItems);
        }

        return $columns;
    }

    private function normalizeItem(Screen $screen, array $item): array
    {
        if (Arr::get($item, 'component') !== 'FormHtmlViewer') {
            return $item;
        }

        $content = Arr::get($item, 'config.content');
        if (is_string($content) && $content !== '') {
            Arr::set($item, 'config.content', $this->replaceDataImagesInHtml($screen, $content));
        }

        return $item;
    }

    private function replaceDataImagesInHtml(Screen $screen, string $html): string
    {
        return preg_replace_callback(
            self::DATA_IMAGE_PATTERN,
            function (array $matches) use ($screen) {
                $dataUri = $matches[0];
                $url = $this->storeDataImage($screen, $dataUri);

                return $url ?? $dataUri;
            },
            $html
        ) ?? $html;
    }

    private function storeDataImage(Screen $screen, string $dataUri): ?string
    {
        if (!preg_match(self::DATA_IMAGE_PATTERN, $dataUri, $matches)) {
            return null;
        }

        $extension = $this->extensionFromMime($matches[1]);
        $payload = $matches[2];
        $hash = hash('sha256', $payload);
        $url = $this->urlForExistingHash($screen, $hash);

        if ($url === null) {
            $url = $this->createMediaFromDataUri($screen, $dataUri, $hash, $extension);
        }

        if ($url !== null) {
            $this->replacedCount++;
        }

        return $url;
    }

    private function urlForExistingHash(Screen $screen, string $hash): ?string
    {
        $existing = $screen->media()
            ->where('collection_name', self::COLLECTION)
            ->where('custom_properties->' . self::CONTENT_HASH_PROPERTY, $hash)
            ->first();

        return $existing?->getUrl();
    }

    private function createMediaFromDataUri(Screen $screen, string $dataUri, string $hash, string $extension): ?string
    {
        try {
            $media = $screen
                ->addMediaFromBase64($dataUri)
                ->usingFileName('inline-' . substr($hash, 0, 12) . '.' . $extension)
                ->withCustomProperties([
                    self::CONTENT_HASH_PROPERTY => $hash,
                    'source' => 'screen_inline_image',
                ])
                ->toMediaCollection(self::COLLECTION);
        } catch (Throwable $exception) {
            Log::warning('Failed to store screen inline image as media', [
                'screen_id' => $screen->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        $this->convertedCount++;

        return $media->getUrl();
    }

    private function extensionFromMime(string $mimeSubtype): string
    {
        $normalized = strtolower($mimeSubtype);
        $map = [
            'jpeg' => 'jpg',
            'svg+xml' => 'svg',
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        $safe = preg_replace('/[^a-z0-9]/', '', $normalized);

        return $safe !== '' ? $safe : 'bin';
    }
}

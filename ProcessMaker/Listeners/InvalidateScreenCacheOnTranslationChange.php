<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Events\TranslationChanged;
use ProcessMaker\Models\Screen;

class InvalidateScreenCacheOnTranslationChange
{
    /**
     * Handle the event.
     */
    public function handle(TranslationChanged $event): void
    {
        try {
            Log::debug('TranslationChanged event received', [
                'event' => [
                    'language' => $event->language,
                    'changes' => $event->changes,
                    'screenId' => $event->screenId,
                ],
            ]);

            if ($event->screenId) {
                Log::debug('Attempting to invalidate screen cache', [
                    'screenId' => $event->screenId,
                    'language' => $event->language,
                ]);

                $this->invalidateScreen($event->screenId, $event->language);
            } else {
                Log::debug('No screenId provided, skipping cache invalidation');
            }

            Log::info('Screen cache invalidated for translation changes', [
                'language' => $event->language,
                'changes' => array_keys($event->changes),
                'screenId' => $event->screenId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate screen cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'language' => $event->language,
                'screenId' => $event->screenId,
            ]);
            throw $e; // Re-throw to ensure error is properly handled
        }
    }

    /**
     * Invalidate cache for a specific screen
     */
    protected function invalidateScreen(string $screenId, string $locale): void
    {
        try {
            Log::debug('Finding screen', ['screenId' => $screenId]);

            $screen = Screen::find($screenId);
            if ($screen) {
                Log::debug('Screen found, getting cache implementation', [
                    'screen' => [
                        'id' => $screen->id,
                        'title' => $screen->title ?? 'N/A',
                    ],
                ]);

                // Get cache implementation from factory
                $cache = ScreenCacheFactory::getScreenCache();
                Log::debug('Cache implementation obtained', [
                    'cacheClass' => get_class($cache),
                ]);

                $result = $cache->invalidate($screen->id, $locale);
                Log::debug('Cache invalidation completed', [
                    'result' => $result,
                    'screenId' => $screen->id,
                    'locale' => $locale,
                ]);
            } else {
                Log::warning('Screen not found', ['screenId' => $screenId]);
            }
        } catch (\Exception $e) {
            Log::error('Error in invalidateScreen', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'screenId' => $screenId,
                'locale' => $locale,
            ]);
            throw $e;
        }
    }
}

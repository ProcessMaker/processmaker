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
            if ($event->screenId) {
                $this->invalidateScreen($event->screenId, $event->language);
            }
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
            $screen = Screen::find($screenId);
            if ($screen) {
                $cache = ScreenCacheFactory::getScreenCache();
                $cache->invalidate($screen->id, $locale);
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

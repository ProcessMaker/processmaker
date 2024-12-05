<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Events\TranslationChanged;
use ProcessMaker\Models\Screen;

class InvalidateScreenCacheOnTranslationChange
{
    protected ScreenCacheManager $cache;

    /**
     * Create the event listener.
     */
    public function __construct(ScreenCacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle the event.
     */
    public function handle(TranslationChanged $event): void
    {
        try {
            if ($event->screenId) {
                // If we know the specific screen, only invalidate that one
                $this->invalidateScreen($event->screenId, $event->locale);
            }
            Log::info('Screen cache invalidated for translation changes', [
                'locale' => $event->locale,
                'changes' => array_keys($event->changes),
                'screenId' => $event->screenId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate screen cache', [
                'error' => $e->getMessage(),
                'locale' => $event->locale,
            ]);
        }
    }

    /**
     * Invalidate cache for a specific screen
     */
    protected function invalidateScreen(string $screenId, string $locale): void
    {
        $screen = Screen::find($screenId);
        if ($screen) {
            $this->cache->invalidate(
                $screen->process_id,
                $screen->process_version_id,
                $locale,
                $screen->id,
                $screen->version_id
            );
        }
    }
}

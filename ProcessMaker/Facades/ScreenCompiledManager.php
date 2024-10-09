<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void storeCompiledContent(string $screenKey, mixed $compiledContent)
 * @method static mixed|null getCompiledContent(string $screenKey)
 * @method static void clearCompiledAssets()
 * @method static string createKey(string $processId, string $processVersionId, string $language, string $screenId, string $screenVersionId)
 * @method static int getLastScreenVersionId()
 * @method static void clearProcessScreensCache(string $processId)
 *
 * @see \ProcessMaker\Managers\ScreenCompiledManager
 */
class ScreenCompiledManager extends Facade
{
    /**
     * Get the registered name of the component in the service container.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'compiledscreen';
    }
}

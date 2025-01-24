<?php

namespace ProcessMaker\Cache\Screens;

use Illuminate\Support\Facades\Facade;

/**
 * Class ScreenCacheFacade
 *
 * @mixin \ProcessMaker\Cache\Settings\ScreenCacheManager
 */
class ScreenCacheFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'screen.cache';
    }
}

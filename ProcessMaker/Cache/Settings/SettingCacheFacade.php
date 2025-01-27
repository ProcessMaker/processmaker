<?php

namespace ProcessMaker\Cache\Settings;

use Illuminate\Support\Facades\Facade;

/**
 * Class SettingCacheFacade
 *
 * @mixin \ProcessMaker\Cache\Settings\SettingCacheManager
 */
class SettingCacheFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'setting.cache';
    }
}

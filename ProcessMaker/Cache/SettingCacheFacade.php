<?php

namespace ProcessMaker\Cache;

use Illuminate\Support\Facades\Facade;

/**
 * Class SettingCacheFacade
 *
 * @mixin \ProcessMaker\Cache\SettingCache
 *
 * @package ProcessMaker\Cache
 */
class SettingCacheFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'setting.cache';
    }
}

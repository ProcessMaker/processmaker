<?php

namespace ProcessMaker\Helpers;

class MobileHelper
{
    public static function isMobile($userAgent)
    {
        $device = '/(Mobile|Android|Tablet|GoBrowser|[0-9]x[0-9]*|uZardWeb\/|Mini|Doris\/|Skyfire\/|iPhone|Fennec\/|Maemo|Iris\/|CLDC\-|Mobi\/)/uis';
        if (preg_match($device, $userAgent) && !empty($_COOKIE['isMobile']) && $_COOKIE['isMobile'] === 'true') {
            return true;
        }

        return false;
    }

    public static function detectMobile()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) && self::isMobile($_SERVER['HTTP_USER_AGENT']);
    }
}

<?php
namespace ProcessMaker;
/**
 * Helper to get modify dates of translation files for cache busting
 */
class i18nHelper {
    public static function availableLangs() {
        $availableLangs = [];
        foreach (self::files() as $key => $file) {
            $availableLangs[] = $key;
        }
        return $availableLangs;
    }

    public static function mdates()
    {
        $mdates = [];
        foreach(self::files() as $key => $file) {
            $mdates[$key] = filemtime(resource_path('lang') . "/" . $file);

        }
        return $mdates;
    }

    private static function files()
    {
        $files = [];
        foreach (scandir(resource_path('lang')) as $file) {
            preg_match("/([a-z]{2})\.json/", $file, $matches);
            if (!empty($matches)) {
                $files[$matches[1]] = $file;
            }
        }
        return $files;
    }
}
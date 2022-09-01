<?php

use function GuzzleHttp\json_encode;
use ProcessMaker\SanitizeHelper;

/**
 * Convert the Laravy menu into associative array
 *
 * @param \Lavary\Menu\Item $menu
 *
 * @return array
 */
function lavaryMenuArray($menu, $includeSubMenus = false)
{
    $children = [];
    $subMenus = $includeSubMenus ? $menu->children() : null;
    if ($subMenus) {
        foreach ($subMenus as $child) {
            $children[] = lavaryMenuArray($child);
        }
    }

    return [
        'title' => __($menu->title),
        'id' => $menu->id,
        'attributes' => $menu->attributes,
        'url' => $menu->url(),
        'children' => $children,
    ];
}

/**
 * Convert the Laravy menu into json string
 *
 * @param \Lavary\Menu\Item $menu
 *
 * @return string
 */
function lavaryMenuJson($menu)
{
    return json_encode(lavaryMenuArray($menu, true));
}

/**
 * Check if a package exists based on its provider name
 *
 * @param string $name
 *
 * @return bool
 */
function hasPackage($name)
{
    $list = \App::make(ProcessMaker\Managers\PackageManager::class)->listPackages();

    return in_array($name, $list);
}

/**
 * Check both the web and api middleware for an existing user
 *
 * @return User
 */
function pmUser()
{
    if (Auth::user()) {
        return Auth::user();
    }
    if (Auth::guard('api')->user()) {
        return Auth::guard('api')->user();
    }

    return null;
}

function sanitizeVueExp($expression)
{
    return SanitizeHelper::sanitizeVueExp($expression);
}

function packTemporalData($data)
{
    // Store data into store/app/private
    $uid = uniqid('data_', true);
    $path = storage_path('app/private/' . $uid);
    file_put_contents($path, \json_encode($data));

    return $uid;
}

function unpackTemporalData($uid)
{
    $path = storage_path('app/private/' . $uid);
    if (file_exists($path)) {
        return \json_decode(file_get_contents($path), true);
    }

    return [];
}

function removeTemporalData($uid)
{
    $path = storage_path('app/private/' . $uid);
    if (file_exists($path)) {
        unlink($path);
    }
}

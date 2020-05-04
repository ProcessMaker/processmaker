<?php
use function GuzzleHttp\json_encode;

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
        'title' => $menu->title,
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

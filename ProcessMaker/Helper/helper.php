<?php
use function GuzzleHttp\json_encode;

/**
 * Convert the Laravy menu into associative array
 *
 * @param \Lavary\Menu\Item $menu
 *
 * @return array
 */
function lavaryMenuArray($menu)
{
    $children = [];
    if ($menu->children()) {
        foreach ($menu->children() as $child) {
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
    return json_encode(lavaryMenuArray($menu));
}

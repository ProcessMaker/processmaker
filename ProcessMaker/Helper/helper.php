<?php
use function GuzzleHttp\json_encode;

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
        'isOpen' => false,
    ];
}

function lavaryMenuJson($menu)
{
    return json_encode(lavaryMenuArray($menu));
}

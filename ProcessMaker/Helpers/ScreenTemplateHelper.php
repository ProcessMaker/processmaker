<?php

namespace ProcessMaker\Helpers;

class ScreenTemplateHelper
{
    /**
     * Remove screen components from the configuration based on the provided components.
     *
     * This method iterates over each page in the configuration and filters out the items
     * based on the provided components. It then returns the updated configuration with
     * the filtered items removed.
     *
     * @param array $config The configuration containing pages with items.
     * @param array $components The components to filter out from the configuration.
     * @return array The updated configuration with filtered items removed.
     */
    public static function removeScreenComponents($config, $components)
    {
        $updatedConfig = [];
        foreach ($config as $page) {
            $filteredPageItems = self::filterPageItems($page['items'] ?? [], $components);
            $page['items'] = $filteredPageItems;
            $updatedConfig[] = $page;
        }

        return $updatedConfig;
    }

    /**
     * Filter items of a page based on the provided components.
     *
     * This method iterates over each item in the page and filters it based on the provided components.
     * If an item passes the filter, it is added to the array of filtered items. If the filtered item
     * is a nested array, it is flattened and merged with the array of filtered items.
     *
     * @param array $items The items of a page to filter.
     * @param array $components The components to filter the items against.
     * @return array The filtered items of the page.
     */
    private static function filterPageItems($items, $components)
    {
        $filteredItems = [];

        foreach ($items as $item) {
            $filteredItem = self::filterItemByComponent($item, $components);
            if ($filteredItem !== null) {
                if (is_array($filteredItem) && !isset($filteredItem['component'])) {
                    $filteredItems = array_merge($filteredItems, self::flattenNestedItems($filteredItem));
                } else {
                    $filteredItems[] = $filteredItem;
                }
            }
        }

        return $filteredItems;
    }

    /**
     * Filter an item based on its component type.
     *
     * This method checks the component type of the item and delegates the filtering process
     * to a specific method if the component is 'FormMultiColumn'. If the item's component
     * is not 'FormMultiColumn', it checks if the item needs to be removed based on the provided
     * screen components. If the item doesn't need to be removed, it returns the item; otherwise,
     * it returns null.
     *
     * @param array $item The item to filter.
     * @param array $components The components to filter against.
     * @return array|null The filtered item or null if it should be removed.
     */
    private static function filterItemByComponent($item, $components)
    {
        if ($item['component'] === 'FormMultiColumn') {
            return self::filterFormMultiColumn($item, $components);
        }

        return !self::removeNestedComponents($item, $components) ? $item : null;
    }

    /**
     * Filter a 'FormMultiColumn' item based on its nested items and the provided screen components.
     *
     * This method determines whether the entire 'FormMultiColumn' item should be removed based on
     * the provided screen components. If the 'FormMultiColumn' item should be removed, it returns
     * an empty array. Otherwise, it filters the nested column items based on the provided screen
     * components and returns the updated 'FormMultiColumn' item with filtered nested items.
     *
     * @param array $item The 'FormMultiColumn' item to filter.
     * @param array $components The components to filter against.
     * @return array The filtered 'FormMultiColumn' item.
     */
    private static function filterFormMultiColumn($item, $components)
    {
        $removeMultiColumn = self::removeNestedComponents($item, $components);
        $filteredMultiColumnItems = $removeMultiColumn ? [] : $item;

        foreach ($item['items'] as $index => $column) {
            $filteredColumnItems = self::filterColumnItems($column, $components, $removeMultiColumn);

            if (isset($filteredMultiColumnItems['items'])) {
                $filteredMultiColumnItems['items'][$index] = $filteredColumnItems;
            } else {
                $filteredMultiColumnItems[] = $filteredColumnItems;
            }
        }

        return $filteredMultiColumnItems;
    }

    /**
     * Check if the item should be removed based on the provided screen components.
     *
     * This method checks if the item's component is in the list of provided components.
     * If the item's component is included in the provided screen components,
     * it returns true indicating that the item should be removed. Otherwise, it returns false.
     *
     * @param array $item The item to check for removal.
     * @param array $components The screen components to filter against.
     * @return bool Whether the item should be removed.
     */
    private static function removeNestedComponents($item, $components)
    {
        $componentList = ['BFormComponent', 'BWrapperComponent'];
        if (in_array($item['component'], $componentList)) {
            $bootstrapComponent = $item['config']['bootstrapComponent'] ?? null;
            if ($bootstrapComponent && isset($components[$item['component']]['bootstrapComponent'])) {
                return in_array($bootstrapComponent, $components[$item['component']]['bootstrapComponent']);
            }
        } else {
            return in_array($item['component'], $components);
        }

        return false;
    }

    /**
     * Filter column items based on the provided components and whether to remove the entire 'FormMultiColumn'.
     *
     * This method iterates over each item in the column and filters it based on the provided components.
     * If an item is a 'FormMultiColumn', it filters its nested columns recursively. If the entire 'FormMultiColumn'
     * should be removed, it adds only the 'FormMultiColumn' item itself to the array of filtered column items.
     * Otherwise, it adds the filtered item to the array of filtered column items. If an item should not be removed
     * based on the provided components, it adds it directly to the array of filtered column items.
     *
     * @param array $column The column items to filter.
     * @param array $components The components to filter against.
     * @param bool $removeMultiColumn Whether the entire 'FormMultiColumn' should be removed.
     * @return array The filtered column items.
     */
    private static function filterColumnItems($column, $components, $removeMultiColumn)
    {
        $filteredColumnItems = [];

        foreach ($column as $colItem) {
            if (isset($colItem['component']) && $colItem['component'] === 'FormMultiColumn') {
                self::filterNestedMultiColumns($colItem, $components, $removeMultiColumn);
                $filteredColumnItems[] = $colItem;
            } elseif (!self::removeNestedComponents($colItem, $components)) {
                $filteredColumnItems[] = $colItem;
            }
        }

        return $filteredColumnItems;
    }

    /**
     * Filter nested columns within a 'FormMultiColumn' item based on the provided components.
     *
     * This method filters the nested columns within a 'FormMultiColumn' item recursively
     * based on the provided components and whether the entire 'FormMultiColumn' item should
     * be removed. If the entire 'FormMultiColumn' item needs to be removed, it replaces it
     * with the filtered nested columns. Otherwise, it updates the 'items' key of the 'FormMultiColumn'
     * item with the filtered nested columns.
     *
     * @param array $item The 'FormMultiColumn' item containing nested columns to filter.
     * @param array $components The components to filter against.
     * @param bool $removeMultiColumn Whether the entire 'FormMultiColumn' should be removed.
     */
    private static function filterNestedMultiColumns(&$item, $components, $removeMultiColumn)
    {
        $multiColumnItems = self::filterNestedColumns($item['items'], $components, $removeMultiColumn);
        if ($removeMultiColumn) {
            $item = $multiColumnItems;
        } else {
            $item['items'] = $multiColumnItems;
        }
    }

    /**
     * Filter nested columns based on the provided components and whether to remove the entire 'FormMultiColumn'.
     *
     * This method iterates over each column containing nested items and filters them based on the provided components.
     * If an item within a column should be removed based on the provided components
     * or whether the entire 'FormMultiColumn' should be removed, it adds it to the array of filtered columns.
     * Otherwise, it adds the item to the array of filtered columns.
     * It then returns the array of filtered columns containing filtered items.
     *
     * @param array $columns The columns containing nested items to filter.
     * @param array $components The components to filter against.
     * @param bool $removeMultiColumn Whether the entire 'FormMultiColumn' should be removed.
     * @return array The filtered columns containing filtered items.
     */
    private static function filterNestedColumns($columns, $components, $removeMultiColumn)
    {
        $filteredColumnItems = [];

        foreach ($columns as $column) {
            $filteredColumn = [];

            foreach ($column as $columnItem) {
                if (self::removeNestedComponents($columnItem, $components)) {
                    if ($columnItem['component'] === 'FormMultiColumn') {
                        self::filterNestedMultiColumns($columnItem, $components, $removeMultiColumn);
                    } elseif ($removeMultiColumn) {
                        $filteredColumn[] = $columnItem;
                    }
                } elseif ($removeMultiColumn) {
                    $filteredColumn[] = $columnItem;
                }
            }

            $filteredColumnItems[] = $filteredColumn;
        }

        return $filteredColumnItems;
    }

    /**
     * Flatten nested items into a single-dimensional array.
     *
     * This method recursively flattens nested items within an array into a single-dimensional array.
     * It iterates over each item in the array, and if the item itself is an array and does not have a 'component' key,
     * indicating it's a nested array, it recursively calls itself to flatten the nested items.
     * Otherwise, it adds the item directly to the flattenedItems array.
     *
     * @param array $items The array containing nested items to flatten.
     * @return array The flattened single-dimensional array containing all items.
     */
    private static function flattenNestedItems($items)
    {
        $flattenedItems = [];
        foreach ($items as $item) {
            if (is_array($item) && !isset($item['component'])) {
                $flattenedItems = array_merge($flattenedItems, self::flattenNestedItems($item));
            } else {
                $flattenedItems[] = $item;
            }
        }

        return $flattenedItems;
    }

    // Parse the CSS string into an associative array
    private static function parseCss($cssString)
    {
        $rules = [];
        // Regex to match complex CSS selectors, allowing for any selector pattern
        preg_match_all('/\[selector="([\w-]+)"\]\s*([^{]+)\s*{([^}]+)}/', $cssString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $baseSelector = $match[1];
            $additionalSelector = trim($match[2]);
            $fullSelector = "[selector=\"$baseSelector\"] " . $additionalSelector;
            $propertiesString = trim($match[3]);

            // Split properties into key-value pairs
            $propertiesArray = explode(';', $propertiesString);
            $properties = [];

            foreach ($propertiesArray as $property) {
                $propertyParts = explode(':', $property);
                if (count($propertyParts) == 2) {
                    $key = trim($propertyParts[0]);
                    $value = trim($propertyParts[1]);
                    if (!empty($key) && !empty($value)) {
                        $properties[$key] = $value;
                    }
                }
            }

            $rules[$fullSelector] = $properties;
        }

        return $rules;
    }

    // Merge the two CSS arrays
    private static function mergeCss($currentCss, $templateCss)
    {
        foreach ($templateCss as $selector => $properties) {
            if (isset($currentCss[$selector])) {
                // Merge properties from Template CSS into the Current Screen CSS for the same selector
                $currentCss[$selector] = array_merge($currentCss[$selector], $properties);
            } else {
                // Add new selector and properties from Template CSS
                $currentCss[$selector] = $properties;
            }
        }

        return $currentCss;
    }

    private static function generateCss($cssArray)
    {
        $cssString = '';

        foreach ($cssArray as $selector => $properties) {
            $cssString .= "$selector {\n";

            foreach ($properties as $key => $value) {
                $cssString .= " $key: $value;\n";
            }

            $cssString .= "}\n\n";
        }

        return $cssString;
    }
}

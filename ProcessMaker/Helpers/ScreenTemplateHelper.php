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
     * Filters and retrieves screen components from the provided configuration.
     *
     * This method processes the given screen configuration, iterating through each page
     * to filter its items based on the specified components. It can optionally remove
     * components from the configuration or keep only the specified components.
     * @param array $config The full screen configuration
     * @param array $components An array of component names that will be used to filter
     *                          the items in the configuration.
     * @param bool $removeComponents (optional) Determines the filtering behavior:
     *        - If 'true', the components in the `$components` array will be removed from the configuration
     *        - If 'false', only the components in the `$components` array will be retained
     *        Defaults to `true`.
     *
     * @return array The updated configuration after filtering the components.
     */
    public static function getScreenComponents($config, $components, $removeComponents = true)
    {
        foreach ($config as $page) {
            $filteredPageItems = self::filterPageItems($page['items'] ?? [], $components, $removeComponents);
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
     * @param bool $removeComponents Whether to remove.
     * @return array The filtered items of the page.
     */
    private static function filterPageItems($items, $components, $removeComponents = true)
    {
        $filteredItems = [];

        foreach ($items as $item) {
            $filteredItem = self::filterItemByComponent($item, $components, $removeComponents);
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
     * @param bool $removeComponents Whether to remove.
     * @return array|null The filtered item or null if it should be removed.
     */
    private static function filterItemByComponent($item, $components, $removeComponents = true)
    {
        if ($item['component'] === 'FormMultiColumn') {
            return self::filterFormMultiColumn($item, $components, $removeComponents);
        }

        return !self::removeNestedComponents($item, $components, $removeComponents) ? $item : null;
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
     * @param bool $removeComponents Whether to remove.
     * @return array The filtered 'FormMultiColumn' item.
     */
    private static function filterFormMultiColumn($item, $components, $removeComponents = true)
    {
        $removeMultiColumn = self::removeNestedComponents($item, $components, $removeComponents);
        $filteredMultiColumnItems = $removeMultiColumn ? [] : $item;

        foreach ($item['items'] as $index => $column) {
            $filteredColumnItems = self::filterColumnItems($column, $components, $removeMultiColumn, $removeComponents);

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
     * @param bool $removeComponents Whether to remove.
     * @return bool Whether the item should be removed.
     */
    private static function removeNestedComponents($item, $components, $removeComponents = true)
    {
        $componentList = ['BFormComponent', 'BWrapperComponent'];
        if (in_array($item['component'], $componentList)) {
            $bootstrapComponent = $item['config']['bootstrapComponent'] ?? null;
            if ($bootstrapComponent && isset($components[$item['component']]['bootstrapComponent'])) {
                return in_array($bootstrapComponent, $components[$item['component']]['bootstrapComponent']);
            }
        } else {
            return in_array($item['component'], $components) && $removeComponents ||
                !$removeComponents && !in_array($item['component'], $components);
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
     * @param bool $removeComponents Whether to remove.
     * @param bool $removeMultiColumn Whether the entire 'FormMultiColumn' should be removed.
     * @return array The filtered column items.
     */
    private static function filterColumnItems($column, $components, $removeMultiColumn, $removeComponents = true)
    {
        $filteredColumnItems = [];

        foreach ($column as $colItem) {
            if (isset($colItem['component']) && $colItem['component'] === 'FormMultiColumn') {
                self::filterNestedMultiColumns($colItem, $components, $removeMultiColumn);
                $filteredColumnItems[] = $colItem;
            } elseif (!self::removeNestedComponents($colItem, $components, $removeComponents)) {
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

    /**
     * Parses a CSS string into an associative array of selectors and their properties.
     *
     * @param string $cssString The CSS string to parse.
     * @return array An associative array where keys are CSS selectors and values are arrays of properties.
     */
    public static function parseCss($cssString)
    {
        $rules = [];

        // Regex to match CSS rules, allowing for comments
        preg_match_all('/([^{]+)\s*\{([^}]*)\}/s', $cssString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $fullSelector = trim($match[1]); // Full CSS selector
            $propertiesString = trim($match[2]); // Properties between the brackets

            $properties = self::parseProperties($propertiesString);

            // Only add to rules if selector and properties are non-empty
            if (!empty($fullSelector) && !empty($properties)) {
                $rules[$fullSelector] = $properties;
            }
        }

        return $rules;
    }

    /**
     * Parses a string of CSS properties and returns an associative array of property-value pairs.
     *
     * @param string $propertiesString The string of CSS properties to parse.
     * @return array An associative array of properties.
     */
    private static function parseProperties($propertiesString)
    {
        $properties = [];

        // Split properties into individual declarations, capturing inline comments
        preg_match_all('/([^;]+;)(?:\s*\/\*.*?\*\/)?/s', $propertiesString, $propertyMatches);

        foreach ($propertyMatches[0] as $property) {
            $property = trim($property);
            $keyValue = self::extractKeyValue($property);

            if ($keyValue) {
                list($key, $value) = $keyValue;

                // Only add to properties if both key and value are non-empty
                if (!empty($key) && !empty($value)) {
                    $properties[$key] = $value; // Add key-value pair
                }
            }
        }

        return $properties;
    }

    /**
     * Extracts the key and value from a CSS property string.
     *
     * @param string $property The CSS property string to extract key-value from.
     * @return array|null An array containing the key and value, or null if not valid.
     */
    private static function extractKeyValue($property)
    {
        if (preg_match('/(.*?)(\/\*.*?\*\/)?$/s', $property, $parts)) {
            $keyValue = explode(':', $parts[1], 2);

            if (count($keyValue) == 2) {
                $key = trim($keyValue[0]);
                $value = trim(rtrim($keyValue[1], ';')); // Trim the trailing semicolon

                // Combine value with inline comment if present
                if (!empty($parts[2])) {
                    $value .= ' ' . trim($parts[2]);
                }

                return [$key, $value]; // Return key and value as an array
            }
        }

        return null; // Return null if the property is not valid
    }

    // Merge the two CSS arrays
    public static function mergeCss($currentCss, $templateCss)
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

    public static function generateCss($cssArray)
    {
        // Convert the CSS array back into a string and output the final CSS
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

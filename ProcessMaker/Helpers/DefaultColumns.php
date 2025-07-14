<?php

namespace ProcessMaker\Helpers;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Package\SavedSearch\Http\Controllers\SavedSearchController;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use ProcessMaker\Package\SavedSearch\Traits\HasDataColumns;

class DefaultColumns
{
    public static function get(string $key)
    {
        $defaultColumns = null;

        // Get default Saved search config
        if (class_exists(SavedSearch::class)) {
            if ($key === SavedSearch::KEY_TASKS) {
                $type = SavedSearch::TYPE_TASK;
            } elseif ($key === SavedSearch::KEY_REQUESTS) {
                $type = SavedSearch::TYPE_REQUEST;
            } else {
                throw new \InvalidArgumentException('Invalid key: ' . $key);
            }

            $defaultSavedSearch = SavedSearch::firstSystemSearchFor(
                Auth::user(),
                $key
            );
            if ($defaultSavedSearch) {
                $defaultColumns = SavedSearchController::adjustColumnsOf(
                    $defaultSavedSearch->columns,
                    $type
                );
            } else {
                $defaultColumns = null;
            }
        } else {
            $defaultColumns = null;
        }

        return $defaultColumns;
    }

    /**
     * Simple verification if columns are default - returns true or false
     *
     * @param array|null $savedColumns The columns to verify
     * @param string $key The key to identify the type can be tasks or requests
     * @return bool True if columns have the same fields as defaults, false otherwise
     */
    public static function verifyDefaultColumns($savedColumns, string $key): bool
    {
        // Handle null savedColumns
        if ($savedColumns === null || !is_array($savedColumns) || empty($savedColumns)) {
            return true;
        }

        // Determine the type based on the key
        $type = null;
        if ($key === SavedSearch::KEY_TASKS) {
            $type = 'task';
        } else {
            return true;
        }

        // Get default columns using the HasDataColumns trait
        $defaultColumns = SavedSearch::getDefaultColumns($type);
        if ($defaultColumns->isEmpty()) {
            return true;
        }

        // Extract only the 'field' values from default columns
        $defaultFields = $defaultColumns->map(function ($column) {
            return $column->field ?? null;
        })->filter()->sort()->values()->toArray();

        // Extract only the 'field' values from saved columns (handling both formats)
        $savedFields = collect($savedColumns)->map(function ($column) {
            // Handle both object and array formats
            if (is_object($column)) {
                return $column->field ?? null;
            } else {
                return $column['field'] ?? null;
            }
        })->filter()->sort()->values()->toArray();

        // Compare only the field arrays
        return $defaultFields == $savedFields;
    }
}

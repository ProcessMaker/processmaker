<?php

namespace ProcessMaker\Helpers;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Package\SavedSearch\Http\Controllers\SavedSearchController;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;

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
                dump('Null 1');
                $defaultColumns = null;
            }
        } else {
            dump('Null 2');
            $defaultColumns = null;
        }

        return $defaultColumns;
    }
}

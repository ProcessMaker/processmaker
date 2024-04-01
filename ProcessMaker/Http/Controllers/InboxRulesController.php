<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Package\SavedSearch\Http\Controllers\SavedSearchController;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;

class InboxRulesController extends Controller
{
    public function index()
    {
        // Get default Saved search config
        if (class_exists(SavedSearch::class)) {
            $defaultSavedSearch = SavedSearch::firstSystemSearchFor(
                Auth::user(),
                SavedSearch::KEY_TASKS,
            );
            if ($defaultSavedSearch) {
                $defaultColumns = SavedSearchController::adjustColumnsOf(
                    $defaultSavedSearch->columns,
                    SavedSearch::TYPE_TASK
                );
            } else {
                $defaultColumns = null;
            }
        } else {
            $defaultColumns = null;
        }

        return view('inbox-rules.index', compact('defaultColumns'));
    }
}

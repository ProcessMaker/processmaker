<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Saved Search Count
    |--------------------------------------------------------------------------
    |
    | This setting determines whether Saved Searches should be counted whenever
    | a workflow action occurs or a Collection record is changed. Disabling
    | removes counts from the sidebar and disables recount queue jobs.
    |
    */

    'count' => env('SAVED_SEARCH_COUNT', true),
    'add_defaults' => env('SAVED_SEARCH_ADD_DEFAULTS', true),

];

<?php

/**
 * OAuth2 Server Related routes
 */
Router::group(['namespace' => 'ProcessMaker\Http\Controllers\OAuth2'], function() {
    // Password Grant and Auth Code Step #2
    Router::post('/oauth2/token', 'OAuth2Controller@token');
    // Auth Code Grant Step #1 (Session is required to redirect to login page and remember user)
    Router::get('/oauth2/authorize', 'OAuth2Controller@getAuthorization')
        ->middleware(['session'])
        ->name('oauth2-authorize');
    // Auth Code Grant Step #1 (Session is required to redirect to login page and remember user)
    Router::post('/oauth2/authorize', 'OAuth2Controller@postAuthorization')->middleware(['session']);
});

// Our standard API calls
Router::group([
    'prefix' => 'api/1.0',
    'namespace' => 'ProcessMaker\Http\Controllers\Api',
], function() {
    Router::group([
        'middleware' => ['auth:api', 'bindings']
    ], function() {
        Router::get('cases/{application}/variables', 'Cases\VariableController@get')->middleware('can:read,application');
        //File manager endpoints.
        Router::get('project/{process}/file-manager', 'Designer\FileManagerController@index')->middleware('can:readProcessFiles,process');
        Router::get('project/{process}/file-manager/{processFile}', 'Designer\FileManagerController@show')->middleware('can:readProcessFiles,process');
        Router::get('project/{process}/file-manager/{processFile}/download', 'Designer\FileManagerController@download')->middleware('can:readProcessFiles,process');
        Router::post('project/{process}/file-manager', 'Designer\FileManagerController@store')->middleware('can:writeProcessFiles,process');
        Router::post('project/{process}/file-manager/{processFile}/upload', 'Designer\FileManagerController@upload')->middleware('can:writeProcessFiles,process');
        Router::put('project/{process}/file-manager/{processFile}', 'Designer\FileManagerController@update')->middleware('can:writeProcessFiles,process');
        Router::delete('project/{process}/file-manager/folder', 'Designer\FileManagerController@removeFolder')->middleware('can:deleteProcessFiles,process');
        Router::delete('project/{process}/file-manager/{processFile}', 'Designer\FileManagerController@remove')->middleware('can:deleteProcessFiles,process');

        Router::post('project/{process}/database-connection', 'Project\DatabaseConnectionController@store')->middleware('can:writeProcessFiles,process');
        Router::put('project/{process}/database-connection/{dbSource}', 'Project\DatabaseConnectionController@update')->middleware('can:writeProcessFiles,process');
        Router::delete('project/{process}/database-connection/{dbSource}', 'Project\DatabaseConnectionController@remove')->middleware('can:deleteProcessFiles,process');
        Router::get('project/{process}/database-connections', 'Project\DatabaseConnectionController@index')->middleware('can:readProcessFiles,process');
        Router::get('project/{process}/database-connection/{dbSource}', 'Project\DatabaseConnectionController@show')->middleware('can:readProcessFiles,process');
        Router::post('project/{process}/database-connection/test', 'Project\DatabaseConnectionController@testConnection')->middleware('can:readProcessFiles,process');

        //Process Category endpoints.
        Router::get('categories', 'Administration\ProcessCategoryController@index');
        Router::get('category/{processCategory}', 'Administration\ProcessCategoryController@show')->middleware('can:read,processCategory');
        Router::post('category', 'Administration\ProcessCategoryController@store');
        Router::put('category/{processCategory}', 'Administration\ProcessCategoryController@update')->middleware('can:write,processCategory');
        Router::delete('category/{processCategory}', 'Administration\ProcessCategoryController@destroy')->middleware('can:delete,processCategory');

        //PmTable endpoints
        Router::get('pmtable', 'Settings\PmTableController@index')->middleware('can:read,ProcessMaker\Model\PmTable');
        Router::get('pmtable/{pmTable}', 'Settings\PmTableController@show')->middleware('can:read,pmTable');
        Router::post('pmtable', 'Settings\PmTableController@store')->middleware('can:write,ProcessMaker\Model\PmTable');
        Router::put('pmtable/{pmTable}', 'Settings\PmTableController@update')->middleware('can:write,pmTable');
        Router::delete('pmtable/{pmTable}', 'Settings\PmTableController@remove')->middleware('can:delete,pmTable');
        Router::get('pmtable/{pmTable}/data', 'Settings\PmTableController@getAllDataRows')->middleware('can:read,pmTable');
        Router::post('pmtable/{pmTable}/data', 'Settings\PmTableController@storeDataRow')->middleware('can:write,pmTable');
        Router::put('pmtable/{pmTable}/data', 'Settings\PmTableController@updateDataRow')->middleware('can:write,pmTable');
        Router::delete('pmtable/{pmTable}/data/{key1}/{value1}/{key2?}/{value2?}/{key3?}/{value3?}', 'Settings\PmTableController@deleteDataRow')->middleware('can:delete,pmTable');

        //Process Variables endpoints.
        Router::get('project/{process}/process-variables', 'Project\ProcessVariableController@index')->middleware('can:read,ProcessMaker\Model\ProcessVariable');
        Router::get('project/{process}/process-variables/{variable}', 'Project\ProcessVariableController@show')->middleware('can:read,ProcessMaker\Model\ProcessVariable');
        Router::post('project/{process}/process-variables', 'Project\ProcessVariableController@store')->middleware('can:write,ProcessMaker\Model\ProcessVariable');
        Router::put('project/{process}/process-variables/{variable}', 'Project\ProcessVariableController@update')->middleware('can:write,variable');
        Router::delete('project/{process}/process-variables/{variable}', 'Project\ProcessVariableController@remove')->middleware('can:delete,variable');

        //Report Tables endpoints
        Router::get('project/{process}/report-tables', 'Project\ReportTableController@index')->middleware('can:read,ProcessMaker\Model\ReportTable');
        Router::get('project/{process}/report-table/{reportTable}', 'Project\ReportTableController@show')->middleware('can:read,reportTable');
        Router::post('project/{process}/report-table', 'Project\ReportTableController@store')->middleware('can:write,ProcessMaker\Model\ReportTable');
        Router::put('project/{process}/report-table/{reportTable}', 'Project\ReportTableController@update')->middleware('can:write,reportTable');
        Router::delete('project/{process}/report-table/{reportTable}', 'Project\ReportTableController@remove')->middleware('can:delete,reportTable');
        Router::get('project/{process}/report-table/{reportTable}/populate', 'Project\ReportTableController@populate')->middleware('can:write,reportTable');
        Router::get('project/{process}/report-table/{reportTable}/data', 'Project\ReportTableController@getAllDataRows')->middleware('can:read,reportTable');

        //Assignee endpoints
        Router::get('project/{process}/activity/{activity}/assignee', 'Designer\AssigneeController@getActivityAssignees')->middleware('can:read');
        Router::post('project/{process}/activity/{activity}/assignee', 'Designer\AssigneeController@store');//->middleware('can:read,ProcessMaker\Model\TaskUser');
        /*Router::get('project/{process}/activity/{activity}/assignee/paged', 'Designer\AssigneeController@getActivityAssigneesPaged')->middleware('can:read');
        Router::get('project/{process}/activity/{activity}/available-assignee', 'Designer\AssigneeController@getActivityAvailableAssignees')->middleware('can:read');
        Router::get('project/{process}/activity/{activity}/available-assignee/paged', 'Designer\AssigneeController@getActivityAvailableAssigneesPaged')->middleware('can:read');
        Router::get('project/{process}/activity/{activity}/assignee/{aas_uid}', 'Designer\AssigneeController@getActivityAssignee')->middleware('can:read');
        Router::get('project/{process}/activity/{activity}/assignee/all', 'Designer\AssigneeController@getActivityAssigneesAll')->middleware('can:read');
        Router::post('project/{process}/activity/{activity}/assignee', 'Designer\AssigneeController@store')->middleware('can:read');
        Router::delete('project/{process}/activity/{activity}/assignee/{aas_uid}', 'Designer\AssigneeController@delete')->middleware('can:read');*/

    });
});

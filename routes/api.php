<?php

/**
 * OAuth2 Server Related routes
 */
Route::group(['namespace' => 'ProcessMaker\Http\Controllers\OAuth2'], function() {
    // Password Grant and Auth Code Step #2
    Route::post('/oauth2/token', 'OAuth2Controller@token');
    // Auth Code Grant Step #1 (Session is required to redirect to login page and remember user)
    Route::get('/oauth2/authorize', 'OAuth2Controller@getAuthorization')
        ->middleware(['session'])
        ->name('oauth2-authorize');
    // Auth Code Grant Step #1 (Session is required to redirect to login page and remember user)
    Route::post('/oauth2/authorize', 'OAuth2Controller@postAuthorization')->middleware(['session']);
});

Route::get('api/1.0/requests','ProcessMaker\Http\Controllers\Api\Requests\RequestsController@index')->middleware('auth');

// Our standard API calls
Route::group([
    'prefix' => 'api/1.0',
    'namespace' => 'ProcessMaker\Http\Controllers\Api',
], function() {
    Route::group([
        'middleware' => ['auth:api', 'bindings']
    ], function() {
        Route::group([
            'middleware' => ['permission:PM_USERS']
        ], function() {
            // Users API Endpoints
            Route::get('users', 'Administration\UsersController@index');
            Route::get('users/{user}', 'Administration\UsersController@get');
            Route::get('users/{user}/avatar', 'Administration\UsersController@avatar');
            Route::put('users/{user}', 'Administration\UsersController@update');
            Route::post('users', 'Administration\UsersController@create');

            // Roles API Endpoints
            Route::get('roles', 'Administration\RolesController@index');
            Route::post('roles', 'Administration\RolesController@create');
            Route::get('roles/{role}', 'Administration\RolesController@get');

            // Roles API Endpoints
            Route::get('groups', 'Administration\GroupsController@index');
            Route::post('groups', 'Administration\GroupsController@create');
            Route::get('groups/{group}', 'Administration\GroupsController@get');

        });

        //User profile

        Route::get('admin/profile', 'Users\ProfileController@profile');
        Route::put('admin/profile', 'Users\ProfileController@updateProfile');

        //File manager endpoints.
        Route::get('project/{process}/file-manager', 'Designer\FileManagerController@index')->middleware('can:readProcessFiles,process');
        Route::get('project/{process}/file-manager/{processFile}', 'Designer\FileManagerController@show')->middleware('can:readProcessFiles,process');
        Route::get('project/{process}/file-manager/{processFile}/download', 'Designer\FileManagerController@download')->middleware('can:readProcessFiles,process');
        Route::post('project/{process}/file-manager', 'Designer\FileManagerController@store')->middleware('can:writeProcessFiles,process');
        Route::post('project/{process}/file-manager/{processFile}/upload', 'Designer\FileManagerController@upload')->middleware('can:writeProcessFiles,process');
        Route::put('project/{process}/file-manager/{processFile}', 'Designer\FileManagerController@update')->middleware('can:writeProcessFiles,process');
        Route::delete('project/{process}/file-manager/folder', 'Designer\FileManagerController@removeFolder')->middleware('can:deleteProcessFiles,process');
        Route::delete('project/{process}/file-manager/{processFile}', 'Designer\FileManagerController@remove')->middleware('can:deleteProcessFiles,process');

        Route::post('project/{process}/database-connection', 'Project\DatabaseConnectionController@store')->middleware('can:writeProcessFiles,process');
        Route::put('project/{process}/database-connection/{dbSource}', 'Project\DatabaseConnectionController@update')->middleware('can:writeProcessFiles,process');
        Route::delete('project/{process}/database-connection/{dbSource}', 'Project\DatabaseConnectionController@remove')->middleware('can:deleteProcessFiles,process');
        Route::get('project/{process}/database-connections', 'Project\DatabaseConnectionController@index')->middleware('can:readProcessFiles,process');
        Route::get('project/{process}/database-connection/{dbSource}', 'Project\DatabaseConnectionController@show')->middleware('can:readProcessFiles,process');
        Route::post('project/{process}/database-connection/test', 'Project\DatabaseConnectionController@testConnection')->middleware('can:readProcessFiles,process');

        //Process Category endpoints.
        Route::get('categories', 'Administration\ProcessCategoryController@index');
        Route::get('category/{processCategory}', 'Administration\ProcessCategoryController@show')->middleware('can:read,processCategory');
        Route::post('category', 'Administration\ProcessCategoryController@store');
        Route::put('category/{processCategory}', 'Administration\ProcessCategoryController@update')->middleware('can:write,processCategory');
        Route::delete('category/{processCategory}', 'Administration\ProcessCategoryController@destroy')->middleware('can:delete,processCategory');

        //PmTable endpoints
        Route::get('pmtable', 'Settings\PmTableController@index')->middleware('can:read,ProcessMaker\Model\PmTable');
        Route::get('pmtable/{pmTable}', 'Settings\PmTableController@show')->middleware('can:read,pmTable');
        Route::post('pmtable', 'Settings\PmTableController@store')->middleware('can:write,ProcessMaker\Model\PmTable');
        Route::put('pmtable/{pmTable}', 'Settings\PmTableController@update')->middleware('can:write,pmTable');
        Route::delete('pmtable/{pmTable}', 'Settings\PmTableController@remove')->middleware('can:delete,pmTable');
        Route::get('pmtable/{pmTable}/data', 'Settings\PmTableController@getAllDataRows')->middleware('can:read,pmTable');
        Route::post('pmtable/{pmTable}/data', 'Settings\PmTableController@storeDataRow')->middleware('can:write,pmTable');
        Route::put('pmtable/{pmTable}/data', 'Settings\PmTableController@updateDataRow')->middleware('can:write,pmTable');
        Route::delete('pmtable/{pmTable}/data/{key1}/{value1}/{key2?}/{value2?}/{key3?}/{value3?}', 'Settings\PmTableController@deleteDataRow')->middleware('can:delete,pmTable');

        //Process Variables endpoints.
        Route::get('project/{process}/process-variables', 'Project\ProcessVariableController@index')->middleware('can:read,ProcessMaker\Model\ProcessVariable');
        Route::get('project/{process}/process-variables/{variable}', 'Project\ProcessVariableController@show')->middleware('can:read,ProcessMaker\Model\ProcessVariable');
        Route::post('project/{process}/process-variables', 'Project\ProcessVariableController@store')->middleware('can:write,ProcessMaker\Model\ProcessVariable');
        Route::put('project/{process}/process-variables/{variable}', 'Project\ProcessVariableController@update')->middleware('can:write,variable');
        Route::delete('project/{process}/process-variables/{variable}', 'Project\ProcessVariableController@remove')->middleware('can:delete,variable');

        // Processes API Endpoints
        Route::group([
            'middleware' => ['permission:PM_FACTORY']
        ], function() {
            Route::get('processes', 'Designer\ProcessesController@index');
            Route::get('processes/{process}', 'Designer\ProcessesController@show');
            Route::delete('processes/{process}', 'Designer\ProcessesController@remove');
            Route::get('processes/{process}/bpmn', 'Designer\ProcessBpmnController@show');
        });

        //Workflow end points
        Route::post('processes/{process}/{processId}/call', 'Workflow\EventController@callProcess');
        Route::post('processes/{process}/events/{event}/trigger', 'Workflow\EventController@triggerStart');
        Route::get('processes/{process}/instances/{instance}/tokens', 'Workflow\TokenController@index');
        Route::post('processes/{process}/instances/{instance}/tokens/{token}/complete', 'Workflow\ActivityController@complete');

        //Report Tables endpoints
        Route::get('process/{process}/report-tables', 'Project\ReportTableController@index')->middleware('can:read,ProcessMaker\Model\ReportTable');
        Route::get('process/{process}/report-table/{reportTable}', 'Project\ReportTableController@show')->middleware('can:read,reportTable');
        Route::post('process/{process}/report-table', 'Project\ReportTableController@store')->middleware('can:write,ProcessMaker\Model\ReportTable');
        Route::put('process/{process}/report-table/{reportTable}', 'Project\ReportTableController@update')->middleware('can:write,reportTable');
        Route::delete('process/{process}/report-table/{reportTable}', 'Project\ReportTableController@remove')->middleware('can:delete,reportTable');
        Route::get('process/{process}/report-table/{reportTable}/populate', 'Project\ReportTableController@populate')->middleware('can:write,reportTable');
        Route::get('process/{process}/report-table/{reportTable}/data', 'Project\ReportTableController@getAllDataRows')->middleware('can:read,reportTable');

        //DynaForm endpoints
        Route::get('process/{process}/forms', 'Designer\FormController@index')->middleware('can:read,ProcessMaker\Model\Form');
        Route::get('process/{process}/form/{form}', 'Designer\FormController@show')->middleware('can:read,ProcessMaker\Model\Form');
        Route::get('process/{process}/form/{form}/grid/{gridName}/field-definitions', 'Designer\FormController@index')->middleware('can:read,ProcessMaker\Model\Form');
        Route::get('process/{process}/form/{form}/grid/{gridName}/field-definition/{fld_id}', 'Designer\FormController@index')->middleware('can:read,ProcessMaker\Model\Form');
        Route::post('process/{process}/form', 'Designer\FormController@store')->middleware('can:write,ProcessMaker\Model\Form');
        Route::put('process/{process}/form/{form}', 'Designer\FormController@update')->middleware('can:write,ProcessMaker\Model\Form');
        Route::delete('process/{process}/form/{form}', 'Designer\FormController@remove')->middleware('can:delete,ProcessMaker\Model\Form');

        //Trigger endpoints
        Route::get('process/{process}/triggers', 'Designer\TriggerController@index')->middleware('can:read,ProcessMaker\Model\Trigger');
        Route::get('process/{process}/trigger/{trigger}', 'Designer\TriggerController@show')->middleware('can:read,ProcessMaker\Model\Trigger');
        Route::post('process/{process}/trigger', 'Designer\TriggerController@store')->middleware('can:write,ProcessMaker\Model\Trigger');
        Route::put('process/{process}/trigger/{trigger}', 'Designer\TriggerController@update')->middleware('can:write,ProcessMaker\Model\Trigger');
        Route::delete('process/{process}/trigger/{trigger}', 'Designer\TriggerController@remove')->middleware('can:delete,ProcessMaker\Model\Trigger');

        //Assignee users o groups to Activity endpoints
        Route::get('process/{process}/activity/{activity}/assignee', 'Designer\AssigneeController@getActivityAssignees')->middleware('can:read,ProcessMaker\Model\TaskUser');
        Route::get('process/{process}/activity/{activity}/assignee/all', 'Designer\AssigneeController@getActivityAssigneesAll')->middleware('can:read,ProcessMaker\Model\TaskUser');
        Route::get('process/{process}/activity/{activity}/assignee/{assignee}', 'Designer\AssigneeController@getActivityAssignee')->middleware('can:read,ProcessMaker\Model\TaskUser');
        Route::post('process/{process}/activity/{activity}/assignee', 'Designer\AssigneeController@store')->middleware('can:write,ProcessMaker\Model\TaskUser');
        Route::delete('process/{process}/activity/{activity}/assignee/{assignee}', 'Designer\AssigneeController@remove')->middleware('can:delete,ProcessMaker\Model\TaskUser');
        Route::get('process/{process}/activity/{activity}/available-assignee', 'Designer\AssigneeController@getActivityAvailable')->middleware('can:read,ProcessMaker\Model\TaskUser');

        //Output Document endpoints
        Route::get('process/{process}/output-documents', 'Designer\OutputDocumentController@index')->middleware('can:read,ProcessMaker\Model\OutputDocument');
        Route::get('process/{process}/output-document/{outputDocument}', 'Designer\OutputDocumentController@show')->middleware('can:read,ProcessMaker\Model\OutputDocument');
        Route::post('process/{process}/output-document', 'Designer\OutputDocumentController@store')->middleware('can:write,ProcessMaker\Model\OutputDocument');
        Route::put('process/{process}/output-document/{outputDocument}', 'Designer\OutputDocumentController@update')->middleware('can:write,ProcessMaker\Model\OutputDocument');
        Route::delete('process/{process}/output-document/{outputDocument}', 'Designer\OutputDocumentController@remove')->middleware('can:delete,ProcessMaker\Model\OutputDocument');

        //Input Document endpoints
        Route::get('process/{process}/input-documents', 'Designer\InputDocumentController@index')->middleware('can:read,ProcessMaker\Model\InputDocument');
        Route::get('process/{process}/input-document/{inputDocument}', 'Designer\InputDocumentController@show')->middleware('can:read,ProcessMaker\Model\InputDocument');
        Route::post('process/{process}/input-document', 'Designer\InputDocumentController@store')->middleware('can:write,ProcessMaker\Model\InputDocument');
        Route::put('process/{process}/input-document/{inputDocument}', 'Designer\InputDocumentController@update')->middleware('can:write,ProcessMaker\Model\InputDocument');
        Route::delete('process/{process}/input-document/{inputDocument}', 'Designer\InputDocumentController@remove')->middleware('can:delete,ProcessMaker\Model\InputDocument');

        //Requests endpoints
        Route::get('requests/{application}/variables', 'Requests\VariableController@get')->middleware('can:read,application');

        //Task Delegations endpoints
        Route::get('tasks', 'Designer\TaskDelegationController@index')->middleware('can:read,ProcessMaker\Model\Delegation');
        Route::get('tasks/{task}', 'Designer\TaskDelegationController@show')->middleware('can:read,ProcessMaker\Model\Delegation');

        //Task endpoints
        Route::get('process/{process}/tasks', 'Designer\TaskController@index')->middleware('can:read,ProcessMaker\Model\Task');
        Route::get('process/{process}/task/{task}', 'Designer\TaskController@show')->middleware('can:read,ProcessMaker\Model\Task');
        Route::post('process/{process}/task', 'Designer\TaskController@store')->middleware('can:write,ProcessMaker\Model\Task');
        Route::put('process/{process}/task/{task}', 'Designer\TaskController@update')->middleware('can:write,ProcessMaker\Model\Task');
        Route::delete('process/{process}/task/{task}', 'Designer\TaskController@remove')->middleware('can:delete,ProcessMaker\Model\Task');

        //Requests endpoints
        Route::get('requests','Requests\RequestsController@index')->middleware('auth');
    });
});

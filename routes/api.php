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

Router::get('api/1.0/cases','ProcessMaker\Http\Controllers\Api\Cases\CasesController@index')->middleware('auth');

// Our standard API calls
Router::group([
    'prefix' => 'api/1.0',
    'namespace' => 'ProcessMaker\Http\Controllers\Api',
], function() {
    Router::group([
        'middleware' => ['auth:api', 'bindings']
    ], function() {
        Router::group([
            'middleware' => ['permission:PM_USERS']
        ], function() {
            // Users API Endpoints
            Router::get('users', 'Administration\UsersController@index');
            Router::get('users/{user}', 'Administration\UsersController@get');

            // Roles API Endpoints
            Router::get('roles', 'Administration\RolesController@index');
            Router::get('roles/{role}', 'Administration\RolesController@get');
        });


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

        // Processes API Endpoints
        Router::group([
            'middleware' => ['permission:PM_FACTORY']
        ], function() {
            Router::get('processes', 'Designer\ProcessesController@index');
            Router::get('processes/{process}', 'Designer\ProcessesController@show');
            Router::delete('project/{process}', 'Designer\ProcessManagerController@remove');
        });

        //Report Tables endpoints
        Router::get('project/{process}/report-tables', 'Project\ReportTableController@index')->middleware('can:read,ProcessMaker\Model\ReportTable');
        Router::get('project/{process}/report-table/{reportTable}', 'Project\ReportTableController@show')->middleware('can:read,reportTable');
        Router::post('project/{process}/report-table', 'Project\ReportTableController@store')->middleware('can:write,ProcessMaker\Model\ReportTable');
        Router::put('project/{process}/report-table/{reportTable}', 'Project\ReportTableController@update')->middleware('can:write,reportTable');
        Router::delete('project/{process}/report-table/{reportTable}', 'Project\ReportTableController@remove')->middleware('can:delete,reportTable');
        Router::get('project/{process}/report-table/{reportTable}/populate', 'Project\ReportTableController@populate')->middleware('can:write,reportTable');
        Router::get('project/{process}/report-table/{reportTable}/data', 'Project\ReportTableController@getAllDataRows')->middleware('can:read,reportTable');

        //DynaForm endpoints
        Router::get('process/{process}/forms', 'Designer\FormController@index')->middleware('can:read,ProcessMaker\Model\Form');
        Router::get('process/{process}/form/{form}', 'Designer\FormController@show')->middleware('can:read,ProcessMaker\Model\Form');
        Router::get('process/{process}/form/{form}/grid/{gridName}/field-definitions', 'Designer\FormController@index')->middleware('can:read,ProcessMaker\Model\Form');
        Router::get('process/{process}/form/{form}/grid/{gridName}/field-definition/{fld_id}', 'Designer\FormController@index')->middleware('can:read,ProcessMaker\Model\Form');
        Router::post('process/{process}/form', 'Designer\FormController@store')->middleware('can:write,ProcessMaker\Model\Form');
        Router::put('process/{process}/form/{form}', 'Designer\FormController@update')->middleware('can:write,ProcessMaker\Model\Form');
        Router::delete('process/{process}/form/{form}', 'Designer\FormController@remove')->middleware('can:delete,ProcessMaker\Model\Form');

        //Trigger endpoints
        Router::get('project/{process}/triggers', 'Designer\TriggerController@index')->middleware('can:read,ProcessMaker\Model\Trigger');
        Router::get('project/{process}/trigger/{trigger}', 'Designer\TriggerController@show')->middleware('can:read,ProcessMaker\Model\Trigger');
        Router::post('project/{process}/trigger', 'Designer\TriggerController@store')->middleware('can:write,ProcessMaker\Model\Trigger');
        Router::put('project/{process}/trigger/{trigger}', 'Designer\TriggerController@update')->middleware('can:write,ProcessMaker\Model\Trigger');
        Router::delete('project/{process}/trigger/{trigger}', 'Designer\TriggerController@remove')->middleware('can:delete,ProcessMaker\Model\Trigger');

        //Assignee users o groups to Activity endpoints
        Router::get('process/{process}/activity/{activity}/assignee', 'Designer\AssigneeController@getActivityAssignees')->middleware('can:read,ProcessMaker\Model\TaskUser');
        Router::get('process/{process}/activity/{activity}/assignee/paged', 'Designer\AssigneeController@getActivityAssigneesPaged')->middleware('can:read,ProcessMaker\Model\TaskUser');
        Router::get('process/{process}/activity/{activity}/assignee/all', 'Designer\AssigneeController@getActivityAssigneesAll')->middleware('can:read,ProcessMaker\Model\TaskUser');
        Router::get('process/{process}/activity/{activity}/assignee/{assignee}', 'Designer\AssigneeController@getActivityAssignee')->middleware('can:read,ProcessMaker\Model\TaskUser');
        Router::post('process/{process}/activity/{activity}/assignee', 'Designer\AssigneeController@store')->middleware('can:write,ProcessMaker\Model\TaskUser');
        Router::delete('process/{process}/activity/{activity}/assignee/{assignee}', 'Designer\AssigneeController@remove')->middleware('can:delete,ProcessMaker\Model\TaskUser');
        Router::get('process/{process}/activity/{activity}/available-assignee', 'Designer\AssigneeController@getActivityAvailable')->middleware('can:read,ProcessMaker\Model\TaskUser');
        Router::get('process/{process}/activity/{activity}/available-assignee/paged', 'Designer\AssigneeController@getActivityAvailablePaged')->middleware('can:read,ProcessMaker\Model\TaskUser');

        //Output Document endpoints
        Router::get('process/{process}/output-documents', 'Designer\OutputDocumentController@index')->middleware('can:read,ProcessMaker\Model\OutputDocument');
        Router::get('process/{process}/output-document/{outputDocument}', 'Designer\OutputDocumentController@show')->middleware('can:read,ProcessMaker\Model\OutputDocument');
        Router::post('process/{process}/output-document', 'Designer\OutputDocumentController@store')->middleware('can:write,ProcessMaker\Model\OutputDocument');
        Router::put('process/{process}/output-document/{outputDocument}', 'Designer\OutputDocumentController@update')->middleware('can:write,ProcessMaker\Model\OutputDocument');
        Router::delete('process/{process}/output-document/{outputDocument}', 'Designer\OutputDocumentController@remove')->middleware('can:delete,ProcessMaker\Model\OutputDocument');

        //Input Document endpoints
        Router::get('project/{process}/input-documents', 'Designer\InputDocumentController@index')->middleware('can:read,ProcessMaker\Model\InputDocument');
        Router::get('project/{process}/input-document/{inputDocument}', 'Designer\InputDocumentController@show')->middleware('can:read,ProcessMaker\Model\InputDocument');
        Router::post('project/{process}/input-document', 'Designer\InputDocumentController@store')->middleware('can:write,ProcessMaker\Model\InputDocument');
        Router::put('project/{process}/input-document/{inputDocument}', 'Designer\InputDocumentController@update')->middleware('can:write,ProcessMaker\Model\InputDocument');
        Router::delete('project/{process}/input-document/{inputDocument}', 'Designer\InputDocumentController@remove')->middleware('can:delete,ProcessMaker\Model\InputDocument');

        //Cases endpoints
        Router::get('cases/{application}/variables', 'Cases\VariableController@get')->middleware('can:read,application');

        //Task Delegations endpoints
        Router::get('tasks', 'Designer\TaskDelegationController@index')->middleware('can:read,ProcessMaker\Model\Delegation');
        Router::get('tasks/{task}', 'Designer\TaskDelegationController@show')->middleware('can:read,ProcessMaker\Model\Delegation');

        //Task endpoints
        Router::get('process/{process}/tasks', 'Designer\TaskController@index')->middleware('can:read,ProcessMaker\Model\Task');
        Router::get('process/{process}/task/{task}', 'Designer\TaskController@show')->middleware('can:read,ProcessMaker\Model\Task');
        Router::post('process/{process}/task', 'Designer\TaskController@store')->middleware('can:write,ProcessMaker\Model\Task');
        Router::put('process/{process}/task/{task}', 'Designer\TaskController@update')->middleware('can:write,ProcessMaker\Model\Task');
        Router::delete('process/{process}/task/{task}', 'Designer\TaskController@remove')->middleware('can:delete,ProcessMaker\Model\Task');

    });
});

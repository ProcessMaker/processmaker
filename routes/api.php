<?php


// Our standard API calls
Route::group([
    'prefix' => 'api/1.0',
    'namespace' => 'ProcessMaker\Http\Controllers\Api',
    'middleware' => ['auth:api', 'bindings']
    ], function () {

        // Environment Variables routes
        Route::get('environment-variables', 'Administration\EnvironmentVariablesController@index');
        Route::post('environment-variables', 'Administration\EnvironmentVariablesController@create');
        Route::get('environment-variables/{variable}', 'Administration\EnvironmentVariablesController@get');
        Route::put('environment-variables/{variable}', 'Administration\EnvironmentVariablesController@update');
        Route::delete('environment-variables/{variable}', 'Administration\EnvironmentVariablesController@delete');


        
        // TEST SCRIPT PREVIEW/EXECUTION
        Route::post('script/preview', 'Designer\ScriptController@preview');

        // Users API Endpoints
        Route::get('users', 'Administration\UsersController@index');
        Route::get('users/{user}', 'Administration\UsersController@get');
        Route::put('users/{user}', 'Administration\UsersController@update');
        Route::post('users', 'Administration\UsersController@create');
        Route::delete('users/{user}', 'Administration\UsersController@delete');

        // Groups API Endpoints
        Route::get('groups', 'Administration\GroupsController@index');
        Route::post('groups', 'Administration\GroupsController@create');
        Route::get('groups/{group}', 'Administration\GroupsController@get');
        Route::delete('groups/{group}', 'Administration\GroupsController@delete');
        Route::put('groups/{group}', 'Administration\GroupsController@update');

        //User profile
        Route::get('admin/profile', 'Users\ProfileController@profile');
        Route::put('admin/profile', 'Users\ProfileController@updateProfile');

        //File manager endpoints.
        Route::get('project/{process}/file-manager', 'Designer\FileManagerController@index');
        Route::get('project/{process}/file-manager/{processFile}', 'Designer\FileManagerController@show');
        Route::get('project/{process}/file-manager/{processFile}/download', 'Designer\FileManagerController@download');
        Route::post('project/{process}/file-manager', 'Designer\FileManagerController@store');
        Route::post('project/{process}/file-manager/{processFile}/upload', 'Designer\FileManagerController@upload');
        Route::put('project/{process}/file-manager/{processFile}', 'Designer\FileManagerController@update');
        Route::delete('project/{process}/file-manager/folder', 'Designer\FileManagerController@removeFolder');
        Route::delete('project/{process}/file-manager/{processFile}', 'Designer\FileManagerController@remove');

        Route::post('project/{process}/database-connection', 'Project\DatabaseConnectionController@store');
        Route::put('project/{process}/database-connection/{dbSource}', 'Project\DatabaseConnectionController@update');
        Route::delete('project/{process}/database-connection/{dbSource}', 'Project\DatabaseConnectionController@remove');
        Route::get('project/{process}/database-connections', 'Project\DatabaseConnectionController@index');
        Route::get('project/{process}/database-connection/{dbSource}', 'Project\DatabaseConnectionController@show');
        Route::post('project/{process}/database-connection/test', 'Project\DatabaseConnectionController@testConnection');

        //Process Category endpoints.
        Route::get('categories', 'Administration\ProcessCategoryController@index');
        Route::get('category/{processCategory}', 'Administration\ProcessCategoryController@show');
        Route::post('category', 'Administration\ProcessCategoryController@store');
        Route::put('category/{processCategory}', 'Administration\ProcessCategoryController@update');
        Route::delete('category/{processCategory}', 'Administration\ProcessCategoryController@destroy');

        //PmTable endpoints
        Route::get('pmtable', 'Settings\PmTableController@index');
        Route::get('pmtable/{pmTable}', 'Settings\PmTableController@show');
        Route::post('pmtable', 'Settings\PmTableController@store');
        Route::put('pmtable/{pmTable}', 'Settings\PmTableController@update');
        Route::delete('pmtable/{pmTable}', 'Settings\PmTableController@remove');
        Route::get('pmtable/{pmTable}/data', 'Settings\PmTableController@getAllDataRows');
        Route::post('pmtable/{pmTable}/data', 'Settings\PmTableController@storeDataRow');
        Route::put('pmtable/{pmTable}/data', 'Settings\PmTableController@updateDataRow');
        Route::delete('pmtable/{pmTable}/data/{key1}/{value1}/{key2?}/{value2?}/{key3?}/{value3?}', 'Settings\PmTableController@deleteDataRow');


        Route::get('processes', 'Designer\ProcessesController@index');
        Route::get('processes/{process}', 'Designer\ProcessesController@show');
        Route::delete('processes/{process}', 'Designer\ProcessesController@remove');
        Route::put('processes/{process}', 'Designer\ProcessesController@update');
        Route::patch('processes/{process}/bpmn', 'Designer\ProcessBpmnController@update');
        Route::get('processes/{process}/bpmn', 'Designer\ProcessBpmnController@show');
        Route::post('processes/create', 'Designer\ProcessesController@createProcessTemplate');

        //Workflow end point
        Route::post('processes/{process}/{processId}/call', 'Workflow\EventController@callProcess');
        Route::post('processes/{process}/events/{event}/trigger', 'Workflow\EventController@triggerStart');
        Route::get('processes/{process}/instances/{instance}/tokens', 'Workflow\TokenController@index');
        Route::post('processes/{process}/instances/{instance}/tokens/{token}/complete', 'Workflow\ActivityController@complete');

        //Report Tables endpoints
        Route::get('process/{process}/report-tables', 'Project\ReportTableController@index');
        Route::get('process/{process}/report-table/{reportTable}', 'Project\ReportTableController@show');
        Route::post('process/{process}/report-table', 'Project\ReportTableController@store');
        Route::put('process/{process}/report-table/{reportTable}', 'Project\ReportTableController@update');
        Route::delete('process/{process}/report-table/{reportTable}', 'Project\ReportTableController@remove');
        Route::get('process/{process}/report-table/{reportTable}/populate', 'Project\ReportTableController@populate');
        Route::get('process/{process}/report-table/{reportTable}/data', 'Project\ReportTableController@getAllDataRows');

        //DynaForm endpoints
        Route::resource('forms', 'Designer\FormController');

        //Trigger endpoints
        Route::get('process/{process}/scripts', 'Designer\ScriptController@index');
        Route::get('process/{process}/script/{script}', 'Designer\ScriptController@show');
        Route::post('process/{process}/script', 'Designer\ScriptController@store');
        Route::put('process/{process}/script/{script}', 'Designer\ScriptController@update');
        Route::delete('process/{process}/script/{script}', 'Designer\ScriptController@remove');

        //Assignee users o groups to Activity endpoints
        Route::get('process/{process}/activity/{activity}/assignee', 'Designer\AssigneeController@getActivityAssignees');
        Route::get('process/{process}/activity/{activity}/assignee/all', 'Designer\AssigneeController@getActivityAssigneesAll');
        Route::get('process/{process}/activity/{activity}/assignee/{assignee}', 'Designer\AssigneeController@getActivityAssignee');
        Route::post('process/{process}/activity/{activity}/assignee', 'Designer\AssigneeController@store');
        Route::delete('process/{process}/activity/{activity}/assignee/{assignee}', 'Designer\AssigneeController@remove');
        Route::get('process/{process}/activity/{activity}/available-assignee', 'Designer\AssigneeController@getActivityAvailable');

        //Output Document endpoints
        Route::get('process/{process}/output-documents', 'Designer\OutputDocumentController@index');
        Route::get('process/{process}/output-document/{outputDocument}', 'Designer\OutputDocumentController@show');
        Route::post('process/{process}/output-document', 'Designer\OutputDocumentController@store');
        Route::put('process/{process}/output-document/{outputDocument}', 'Designer\OutputDocumentController@update');
        Route::delete('process/{process}/output-document/{outputDocument}', 'Designer\OutputDocumentController@remove');

        //Input Document endpoints
        Route::get('process/{process}/input-documents', 'Designer\InputDocumentController@index');
        Route::get('process/{process}/input-document/{inputDocument}', 'Designer\InputDocumentController@show');
        Route::post('process/{process}/input-document', 'Designer\InputDocumentController@store');
        Route::put('process/{process}/input-document/{inputDocument}', 'Designer\InputDocumentController@update');
        Route::delete('process/{process}/input-document/{inputDocument}', 'Designer\InputDocumentController@remove');

        //Requests endpoints
        Route::get('requests', 'Requests\RequestsController@index');

        //Task Delegations endpoints
        Route::get('tasks', 'Designer\TaskDelegationController@index');
        Route::get('tasks/{task}', 'Designer\TaskDelegationController@show');

        //Task endpoints
        Route::get('process/{process}/tasks', 'Designer\TaskController@index');
        Route::get('process/{process}/task/{task}', 'Designer\TaskController@show');
        Route::post('process/{process}/task', 'Designer\TaskController@store');
        Route::put('process/{process}/task/{task}', 'Designer\TaskController@update');
        Route::delete('process/{process}/task/{task}', 'Designer\TaskController@remove');

        //Requests endpoints
        Route::get('user/processes', 'Requests\RequestsController@getUserStartProcesses');

});

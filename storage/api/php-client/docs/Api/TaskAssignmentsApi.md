# OpenAPI\Client\TaskAssignmentsApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**createTaskAssignments**](TaskAssignmentsApi.md#createTaskAssignments) | **POST** /task_assignments | Save a new task assignments
[**updateTaskAssignments**](TaskAssignmentsApi.md#updateTaskAssignments) | **PUT** /task_assignments/{task_assignments_id} | Update a task assignments


# **createTaskAssignments**
> \OpenAPI\Client\Model\TaskAssignments createTaskAssignments($task_assignments_editable)

Save a new task assignments

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\TaskAssignmentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$task_assignments_editable = new \OpenAPI\Client\Model\TaskAssignmentsEditable(); // \OpenAPI\Client\Model\TaskAssignmentsEditable | 

try {
    $result = $apiInstance->createTaskAssignments($task_assignments_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TaskAssignmentsApi->createTaskAssignments: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **task_assignments_editable** | [**\OpenAPI\Client\Model\TaskAssignmentsEditable**](../Model/TaskAssignmentsEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\TaskAssignments**](../Model/TaskAssignments.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateTaskAssignments**
> \OpenAPI\Client\Model\TaskAssignments updateTaskAssignments($task_assignments_id, $task_assignments_editable)

Update a task assignments

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\TaskAssignmentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$task_assignments_id = 'task_assignments_id_example'; // string | ID of task assignment to return
$task_assignments_editable = new \OpenAPI\Client\Model\TaskAssignmentsEditable(); // \OpenAPI\Client\Model\TaskAssignmentsEditable | 

try {
    $result = $apiInstance->updateTaskAssignments($task_assignments_id, $task_assignments_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TaskAssignmentsApi->updateTaskAssignments: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **task_assignments_id** | **string**| ID of task assignment to return |
 **task_assignments_editable** | [**\OpenAPI\Client\Model\TaskAssignmentsEditable**](../Model/TaskAssignmentsEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\TaskAssignments**](../Model/TaskAssignments.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


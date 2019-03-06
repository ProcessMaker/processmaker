# OpenAPI\Client\ProcessRequestsApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**deleteProcessRequest**](ProcessRequestsApi.md#deleteProcessRequest) | **DELETE** /requests/process_request_id | Delete a process request
[**getProcessRequestById**](ProcessRequestsApi.md#getProcessRequestById) | **GET** /requests/process_request_id | Get single process request by ID
[**getProcessesRequests**](ProcessRequestsApi.md#getProcessesRequests) | **GET** /requests | Returns all process Requests that the user has access to
[**updateProcessRequest**](ProcessRequestsApi.md#updateProcessRequest) | **PUT** /requests/process_request_id | Update a process request


# **deleteProcessRequest**
> \OpenAPI\Client\Model\Requests deleteProcessRequest($process_id)

Delete a process request

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessRequestsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_id = 'process_id_example'; // string | ID of process request to return

try {
    $result = $apiInstance->deleteProcessRequest($process_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProcessRequestsApi->deleteProcessRequest: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_id** | **string**| ID of process request to return |

### Return type

[**\OpenAPI\Client\Model\Requests**](../Model/Requests.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getProcessRequestById**
> \OpenAPI\Client\Model\Requests getProcessRequestById($process_id)

Get single process request by ID

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessRequestsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_id = 'process_id_example'; // string | ID of process request to return

try {
    $result = $apiInstance->getProcessRequestById($process_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProcessRequestsApi->getProcessRequestById: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_id** | **string**| ID of process request to return |

### Return type

[**\OpenAPI\Client\Model\Requests**](../Model/Requests.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getProcessesRequests**
> \OpenAPI\Client\Model\InlineResponse20014 getProcessesRequests($type, $filter, $order_by, $order_direction, $per_page, $include)

Returns all process Requests that the user has access to

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessRequestsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string | Only return requests by type
$filter = 'filter_example'; // string | Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring.
$order_by = 'order_by_example'; // string | Field to order results by
$order_direction = 'asc'; // string | 
$per_page = 10; // int | 
$include = 'include_example'; // string | Include data from related models in payload. Comma seperated list.

try {
    $result = $apiInstance->getProcessesRequests($type, $filter, $order_by, $order_direction, $per_page, $include);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProcessRequestsApi->getProcessesRequests: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **type** | **string**| Only return requests by type | [optional]
 **filter** | **string**| Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring. | [optional]
 **order_by** | **string**| Field to order results by | [optional]
 **order_direction** | **string**|  | [optional] [default to &#39;asc&#39;]
 **per_page** | **int**|  | [optional] [default to 10]
 **include** | **string**| Include data from related models in payload. Comma seperated list. | [optional]

### Return type

[**\OpenAPI\Client\Model\InlineResponse20014**](../Model/InlineResponse20014.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateProcessRequest**
> \OpenAPI\Client\Model\Requests updateProcessRequest($process_id, $requests_editable)

Update a process request

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessRequestsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_id = 'process_id_example'; // string | ID of process request to return
$requests_editable = new \OpenAPI\Client\Model\RequestsEditable(); // \OpenAPI\Client\Model\RequestsEditable | 

try {
    $result = $apiInstance->updateProcessRequest($process_id, $requests_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProcessRequestsApi->updateProcessRequest: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_id** | **string**| ID of process request to return |
 **requests_editable** | [**\OpenAPI\Client\Model\RequestsEditable**](../Model/RequestsEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\Requests**](../Model/Requests.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


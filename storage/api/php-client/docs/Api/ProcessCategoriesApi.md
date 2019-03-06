# OpenAPI\Client\ProcessCategoriesApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**createProcessCategory**](ProcessCategoriesApi.md#createProcessCategory) | **POST** /process_categories | Save a new process Category
[**deleteProcessCategory**](ProcessCategoriesApi.md#deleteProcessCategory) | **DELETE** /process_categories/process_category_id | Delete a process category
[**getProcessCategories**](ProcessCategoriesApi.md#getProcessCategories) | **GET** /process_categories | Returns all processes categories that the user has access to
[**getProcessCategoryById**](ProcessCategoriesApi.md#getProcessCategoryById) | **GET** /process_categories/process_category_id | Get single process category by ID
[**updateProcessCategory**](ProcessCategoriesApi.md#updateProcessCategory) | **PUT** /process_categories/process_category_id | Update a process Category


# **createProcessCategory**
> \OpenAPI\Client\Model\ProcessCategory createProcessCategory($process_category_editable)

Save a new process Category

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessCategoriesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_category_editable = new \OpenAPI\Client\Model\ProcessCategoryEditable(); // \OpenAPI\Client\Model\ProcessCategoryEditable | 

try {
    $result = $apiInstance->createProcessCategory($process_category_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProcessCategoriesApi->createProcessCategory: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_category_editable** | [**\OpenAPI\Client\Model\ProcessCategoryEditable**](../Model/ProcessCategoryEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\ProcessCategory**](../Model/ProcessCategory.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **deleteProcessCategory**
> \OpenAPI\Client\Model\Process deleteProcessCategory($process_category_id)

Delete a process category

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessCategoriesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_category_id = 'process_category_id_example'; // string | ID of process category to return

try {
    $result = $apiInstance->deleteProcessCategory($process_category_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProcessCategoriesApi->deleteProcessCategory: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_category_id** | **string**| ID of process category to return |

### Return type

[**\OpenAPI\Client\Model\Process**](../Model/Process.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getProcessCategories**
> \OpenAPI\Client\Model\InlineResponse20011 getProcessCategories($filter, $order_by, $order_direction, $per_page, $include)

Returns all processes categories that the user has access to

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessCategoriesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$filter = 'filter_example'; // string | Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring.
$order_by = 'order_by_example'; // string | Field to order results by
$order_direction = 'asc'; // string | 
$per_page = 10; // int | 
$include = 'include_example'; // string | Include data from related models in payload. Comma seperated list.

try {
    $result = $apiInstance->getProcessCategories($filter, $order_by, $order_direction, $per_page, $include);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProcessCategoriesApi->getProcessCategories: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **filter** | **string**| Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring. | [optional]
 **order_by** | **string**| Field to order results by | [optional]
 **order_direction** | **string**|  | [optional] [default to &#39;asc&#39;]
 **per_page** | **int**|  | [optional] [default to 10]
 **include** | **string**| Include data from related models in payload. Comma seperated list. | [optional]

### Return type

[**\OpenAPI\Client\Model\InlineResponse20011**](../Model/InlineResponse20011.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getProcessCategoryById**
> \OpenAPI\Client\Model\ProcessCategory getProcessCategoryById($process_category_id)

Get single process category by ID

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessCategoriesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_category_id = 'process_category_id_example'; // string | ID of process category to return

try {
    $result = $apiInstance->getProcessCategoryById($process_category_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProcessCategoriesApi->getProcessCategoryById: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_category_id** | **string**| ID of process category to return |

### Return type

[**\OpenAPI\Client\Model\ProcessCategory**](../Model/ProcessCategory.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateProcessCategory**
> \OpenAPI\Client\Model\ProcessCategory updateProcessCategory($process_category_id, $process_category_editable)

Update a process Category

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessCategoriesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_category_id = 'process_category_id_example'; // string | ID of process category to return
$process_category_editable = new \OpenAPI\Client\Model\ProcessCategoryEditable(); // \OpenAPI\Client\Model\ProcessCategoryEditable | 

try {
    $result = $apiInstance->updateProcessCategory($process_category_id, $process_category_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProcessCategoriesApi->updateProcessCategory: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_category_id** | **string**| ID of process category to return |
 **process_category_editable** | [**\OpenAPI\Client\Model\ProcessCategoryEditable**](../Model/ProcessCategoryEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\ProcessCategory**](../Model/ProcessCategory.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


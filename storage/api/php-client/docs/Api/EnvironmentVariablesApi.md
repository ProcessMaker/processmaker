# OpenAPI\Client\EnvironmentVariablesApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**createEnvironmentVariables**](EnvironmentVariablesApi.md#createEnvironmentVariables) | **POST** /environment_variables | Save a new environment_variables
[**deleteEnvironmentVariables**](EnvironmentVariablesApi.md#deleteEnvironmentVariables) | **DELETE** /environment_variables/{environment_variables_id} | Delete a environment_variables
[**getEnvironmentVariables**](EnvironmentVariablesApi.md#getEnvironmentVariables) | **GET** /environment_variables | Returns all environmentVariables that the user has access to
[**getEnvironmentVariablesById**](EnvironmentVariablesApi.md#getEnvironmentVariablesById) | **GET** /environment_variables/{environment_variables_id} | Get single environment_variables by ID
[**updateEnvironmentVariables**](EnvironmentVariablesApi.md#updateEnvironmentVariables) | **PUT** /environment_variables/{environment_variables_id} | Update a environment_variables


# **createEnvironmentVariables**
> \OpenAPI\Client\Model\EnvironmentVariables createEnvironmentVariables($environment_variables_editable)

Save a new environment_variables

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\EnvironmentVariablesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$environment_variables_editable = new \OpenAPI\Client\Model\EnvironmentVariablesEditable(); // \OpenAPI\Client\Model\EnvironmentVariablesEditable | 

try {
    $result = $apiInstance->createEnvironmentVariables($environment_variables_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EnvironmentVariablesApi->createEnvironmentVariables: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **environment_variables_editable** | [**\OpenAPI\Client\Model\EnvironmentVariablesEditable**](../Model/EnvironmentVariablesEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\EnvironmentVariables**](../Model/EnvironmentVariables.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **deleteEnvironmentVariables**
> \OpenAPI\Client\Model\EnvironmentVariables deleteEnvironmentVariables($environment_variables_id)

Delete a environment_variables

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\EnvironmentVariablesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$environment_variables_id = 'environment_variables_id_example'; // string | ID of environment_variables to return

try {
    $result = $apiInstance->deleteEnvironmentVariables($environment_variables_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EnvironmentVariablesApi->deleteEnvironmentVariables: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **environment_variables_id** | **string**| ID of environment_variables to return |

### Return type

[**\OpenAPI\Client\Model\EnvironmentVariables**](../Model/EnvironmentVariables.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getEnvironmentVariables**
> \OpenAPI\Client\Model\InlineResponse2001 getEnvironmentVariables($filter, $order_by, $order_direction, $per_page, $include)

Returns all environmentVariables that the user has access to

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\EnvironmentVariablesApi(
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
    $result = $apiInstance->getEnvironmentVariables($filter, $order_by, $order_direction, $per_page, $include);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EnvironmentVariablesApi->getEnvironmentVariables: ', $e->getMessage(), PHP_EOL;
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

[**\OpenAPI\Client\Model\InlineResponse2001**](../Model/InlineResponse2001.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getEnvironmentVariablesById**
> \OpenAPI\Client\Model\EnvironmentVariables getEnvironmentVariablesById($environment_variables_id)

Get single environment_variables by ID

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\EnvironmentVariablesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$environment_variables_id = 'environment_variables_id_example'; // string | ID of environment_variables to return

try {
    $result = $apiInstance->getEnvironmentVariablesById($environment_variables_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EnvironmentVariablesApi->getEnvironmentVariablesById: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **environment_variables_id** | **string**| ID of environment_variables to return |

### Return type

[**\OpenAPI\Client\Model\EnvironmentVariables**](../Model/EnvironmentVariables.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateEnvironmentVariables**
> \OpenAPI\Client\Model\EnvironmentVariables updateEnvironmentVariables($environment_variables_id, $environment_variables_editable)

Update a environment_variables

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\EnvironmentVariablesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$environment_variables_id = 'environment_variables_id_example'; // string | ID of environment_variables to return
$environment_variables_editable = new \OpenAPI\Client\Model\EnvironmentVariablesEditable(); // \OpenAPI\Client\Model\EnvironmentVariablesEditable | 

try {
    $result = $apiInstance->updateEnvironmentVariables($environment_variables_id, $environment_variables_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EnvironmentVariablesApi->updateEnvironmentVariables: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **environment_variables_id** | **string**| ID of environment_variables to return |
 **environment_variables_editable** | [**\OpenAPI\Client\Model\EnvironmentVariablesEditable**](../Model/EnvironmentVariablesEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\EnvironmentVariables**](../Model/EnvironmentVariables.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


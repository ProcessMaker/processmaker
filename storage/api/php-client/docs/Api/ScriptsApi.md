# OpenAPI\Client\ScriptsApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**createScript**](ScriptsApi.md#createScript) | **POST** /scripts | Save a new script
[**deleteScript**](ScriptsApi.md#deleteScript) | **DELETE** /scripts/scriptsId | Delete a script
[**getScripts**](ScriptsApi.md#getScripts) | **GET** /scripts | Returns all scripts that the user has access to
[**getScriptsById**](ScriptsApi.md#getScriptsById) | **GET** /scripts/scriptsId | Get single script by ID
[**getScriptsPreview**](ScriptsApi.md#getScriptsPreview) | **GET** /scripts/ew | Returns all scripts that the user has access to
[**updateScreen**](ScriptsApi.md#updateScreen) | **PUT** /scripts/scriptsId/duplicate | duplicate a script
[**updateScript**](ScriptsApi.md#updateScript) | **PUT** /scripts/scriptsId | Update a script


# **createScript**
> \OpenAPI\Client\Model\Scripts createScript($scripts_editable)

Save a new script

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScriptsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$scripts_editable = new \OpenAPI\Client\Model\ScriptsEditable(); // \OpenAPI\Client\Model\ScriptsEditable | 

try {
    $result = $apiInstance->createScript($scripts_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScriptsApi->createScript: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **scripts_editable** | [**\OpenAPI\Client\Model\ScriptsEditable**](../Model/ScriptsEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\Scripts**](../Model/Scripts.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **deleteScript**
> \OpenAPI\Client\Model\Scripts deleteScript($script_id)

Delete a script

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScriptsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$script_id = 'script_id_example'; // string | ID of script to return

try {
    $result = $apiInstance->deleteScript($script_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScriptsApi->deleteScript: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **script_id** | **string**| ID of script to return |

### Return type

[**\OpenAPI\Client\Model\Scripts**](../Model/Scripts.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getScripts**
> \OpenAPI\Client\Model\InlineResponse20017 getScripts($filter, $order_by, $order_direction, $per_page, $include)

Returns all scripts that the user has access to

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScriptsApi(
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
    $result = $apiInstance->getScripts($filter, $order_by, $order_direction, $per_page, $include);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScriptsApi->getScripts: ', $e->getMessage(), PHP_EOL;
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

[**\OpenAPI\Client\Model\InlineResponse20017**](../Model/InlineResponse20017.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getScriptsById**
> \OpenAPI\Client\Model\Scripts getScriptsById($script_id)

Get single script by ID

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScriptsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$script_id = 'script_id_example'; // string | ID of script to return

try {
    $result = $apiInstance->getScriptsById($script_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScriptsApi->getScriptsById: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **script_id** | **string**| ID of script to return |

### Return type

[**\OpenAPI\Client\Model\Scripts**](../Model/Scripts.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getScriptsPreview**
> object getScriptsPreview($data, $config, $code, $language)

Returns all scripts that the user has access to

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScriptsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$data = 'data_example'; // string | 
$config = 'config_example'; // string | 
$code = 'code_example'; // string | 
$language = 'language_example'; // string | 

try {
    $result = $apiInstance->getScriptsPreview($data, $config, $code, $language);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScriptsApi->getScriptsPreview: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **data** | **string**|  | [optional]
 **config** | **string**|  | [optional]
 **code** | **string**|  | [optional]
 **language** | **string**|  | [optional]

### Return type

**object**

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateScreen**
> \OpenAPI\Client\Model\Scripts updateScreen($screens_id, $screens_editable)

duplicate a script

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScriptsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$screens_id = 'screens_id_example'; // string | ID of script to return
$screens_editable = new \OpenAPI\Client\Model\ScreensEditable(); // \OpenAPI\Client\Model\ScreensEditable | 

try {
    $result = $apiInstance->updateScreen($screens_id, $screens_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScriptsApi->updateScreen: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **screens_id** | **string**| ID of script to return |
 **screens_editable** | [**\OpenAPI\Client\Model\ScreensEditable**](../Model/ScreensEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\Scripts**](../Model/Scripts.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateScript**
> \OpenAPI\Client\Model\Scripts updateScript($script_id, $scripts_editable)

Update a script

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScriptsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$script_id = 'script_id_example'; // string | ID of script to return
$scripts_editable = new \OpenAPI\Client\Model\ScriptsEditable(); // \OpenAPI\Client\Model\ScriptsEditable | 

try {
    $result = $apiInstance->updateScript($script_id, $scripts_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScriptsApi->updateScript: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **script_id** | **string**| ID of script to return |
 **scripts_editable** | [**\OpenAPI\Client\Model\ScriptsEditable**](../Model/ScriptsEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\Scripts**](../Model/Scripts.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


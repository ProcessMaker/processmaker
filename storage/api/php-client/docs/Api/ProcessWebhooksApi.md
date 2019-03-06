# OpenAPI\Client\ProcessWebhooksApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**createProcessWebhook**](ProcessWebhooksApi.md#createProcessWebhook) | **POST** /processes/{process_id}/webhooks/ | Save a new webhook for a start node
[**deleteProcessWebhook**](ProcessWebhooksApi.md#deleteProcessWebhook) | **DELETE** /processes/{process_id}/webhooks/ | Delete (revoke) a webhook for a start node
[**getProcessWebhook**](ProcessWebhooksApi.md#getProcessWebhook) | **GET** /processes/{process_id}/webhooks/ | Get the webhook for a start node


# **createProcessWebhook**
> createProcessWebhook($process_id, $node)

Save a new webhook for a start node

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessWebhooksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_id = 56; // int | ID of process
$node = 'node_example'; // string | Start event node ID

try {
    $apiInstance->createProcessWebhook($process_id, $node);
} catch (Exception $e) {
    echo 'Exception when calling ProcessWebhooksApi->createProcessWebhook: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_id** | **int**| ID of process |
 **node** | **string**| Start event node ID |

### Return type

void (empty response body)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **deleteProcessWebhook**
> deleteProcessWebhook($process_id, $node)

Delete (revoke) a webhook for a start node

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessWebhooksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_id = 56; // int | ID of process
$node = 'node_example'; // string | Start event node ID

try {
    $apiInstance->deleteProcessWebhook($process_id, $node);
} catch (Exception $e) {
    echo 'Exception when calling ProcessWebhooksApi->deleteProcessWebhook: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_id** | **int**| ID of process |
 **node** | **string**| Start event node ID |

### Return type

void (empty response body)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getProcessWebhook**
> getProcessWebhook($process_id, $node)

Get the webhook for a start node

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ProcessWebhooksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$process_id = 56; // int | ID of process
$node = 'node_example'; // string | Start event node ID

try {
    $apiInstance->getProcessWebhook($process_id, $node);
} catch (Exception $e) {
    echo 'Exception when calling ProcessWebhooksApi->getProcessWebhook: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **process_id** | **int**| ID of process |
 **node** | **string**| Start event node ID |

### Return type

void (empty response body)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


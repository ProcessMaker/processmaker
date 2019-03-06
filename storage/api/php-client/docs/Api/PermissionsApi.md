# OpenAPI\Client\PermissionsApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**processMakerHttpControllersApiPermissionControllerUpdate**](PermissionsApi.md#processMakerHttpControllersApiPermissionControllerUpdate) | **PUT** /permissions | Update the permissions of an user


# **processMakerHttpControllersApiPermissionControllerUpdate**
> processMakerHttpControllersApiPermissionControllerUpdate($inline_object4)

Update the permissions of an user

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\PermissionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$inline_object4 = new \OpenAPI\Client\Model\InlineObject4(); // \OpenAPI\Client\Model\InlineObject4 | 

try {
    $apiInstance->processMakerHttpControllersApiPermissionControllerUpdate($inline_object4);
} catch (Exception $e) {
    echo 'Exception when calling PermissionsApi->processMakerHttpControllersApiPermissionControllerUpdate: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **inline_object4** | [**\OpenAPI\Client\Model\InlineObject4**](../Model/InlineObject4.md)|  |

### Return type

void (empty response body)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


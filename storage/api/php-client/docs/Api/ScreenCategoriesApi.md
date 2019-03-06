# OpenAPI\Client\ScreenCategoriesApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**createScreenCategory**](ScreenCategoriesApi.md#createScreenCategory) | **POST** /screen_categories | Save a new Screen Category
[**deleteScreenCategory**](ScreenCategoriesApi.md#deleteScreenCategory) | **DELETE** /screen_categories/screen_category_id | Delete a screen category
[**getScreenCategories**](ScreenCategoriesApi.md#getScreenCategories) | **GET** /screen_categories | Returns all screens categories that the user has access to
[**getScreenCategoryById**](ScreenCategoriesApi.md#getScreenCategoryById) | **GET** /screen_categories/screen_category_id | Get single screen category by ID
[**updateScreenCategory**](ScreenCategoriesApi.md#updateScreenCategory) | **PUT** /screen_categories/screen_category_id | Update a screen Category


# **createScreenCategory**
> \OpenAPI\Client\Model\ScreenCategory createScreenCategory($screen_category_editable)

Save a new Screen Category

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScreenCategoriesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$screen_category_editable = new \OpenAPI\Client\Model\ScreenCategoryEditable(); // \OpenAPI\Client\Model\ScreenCategoryEditable | 

try {
    $result = $apiInstance->createScreenCategory($screen_category_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScreenCategoriesApi->createScreenCategory: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **screen_category_editable** | [**\OpenAPI\Client\Model\ScreenCategoryEditable**](../Model/ScreenCategoryEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\ScreenCategory**](../Model/ScreenCategory.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **deleteScreenCategory**
> \OpenAPI\Client\Model\ScreenCategory deleteScreenCategory($screen_category_id)

Delete a screen category

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScreenCategoriesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$screen_category_id = 'screen_category_id_example'; // string | ID of screen category to return

try {
    $result = $apiInstance->deleteScreenCategory($screen_category_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScreenCategoriesApi->deleteScreenCategory: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **screen_category_id** | **string**| ID of screen category to return |

### Return type

[**\OpenAPI\Client\Model\ScreenCategory**](../Model/ScreenCategory.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getScreenCategories**
> \OpenAPI\Client\Model\InlineResponse20015 getScreenCategories($filter, $order_by, $order_direction, $per_page, $include)

Returns all screens categories that the user has access to

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScreenCategoriesApi(
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
    $result = $apiInstance->getScreenCategories($filter, $order_by, $order_direction, $per_page, $include);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScreenCategoriesApi->getScreenCategories: ', $e->getMessage(), PHP_EOL;
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

[**\OpenAPI\Client\Model\InlineResponse20015**](../Model/InlineResponse20015.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getScreenCategoryById**
> \OpenAPI\Client\Model\ScreenCategory getScreenCategoryById($screen_category_id)

Get single screen category by ID

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScreenCategoriesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$screen_category_id = 'screen_category_id_example'; // string | ID of screen category to return

try {
    $result = $apiInstance->getScreenCategoryById($screen_category_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScreenCategoriesApi->getScreenCategoryById: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **screen_category_id** | **string**| ID of screen category to return |

### Return type

[**\OpenAPI\Client\Model\ScreenCategory**](../Model/ScreenCategory.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateScreenCategory**
> \OpenAPI\Client\Model\ScreenCategory updateScreenCategory($screen_category_id, $screen_category_editable)

Update a screen Category

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\ScreenCategoriesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$screen_category_id = 'screen_category_id_example'; // string | ID of screen category to return
$screen_category_editable = new \OpenAPI\Client\Model\ScreenCategoryEditable(); // \OpenAPI\Client\Model\ScreenCategoryEditable | 

try {
    $result = $apiInstance->updateScreenCategory($screen_category_id, $screen_category_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ScreenCategoriesApi->updateScreenCategory: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **screen_category_id** | **string**| ID of screen category to return |
 **screen_category_editable** | [**\OpenAPI\Client\Model\ScreenCategoryEditable**](../Model/ScreenCategoryEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\ScreenCategory**](../Model/ScreenCategory.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


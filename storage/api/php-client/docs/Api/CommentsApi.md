# OpenAPI\Client\CommentsApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**createComments**](CommentsApi.md#createComments) | **POST** /comments | Save a new comment
[**deleteComments**](CommentsApi.md#deleteComments) | **DELETE** /comments/id | Delete a comments
[**getCommentById**](CommentsApi.md#getCommentById) | **GET** /comments/commentId | Get single comment by ID
[**getComments**](CommentsApi.md#getComments) | **GET** /comments | Returns all comments for a given type
[**updateComment**](CommentsApi.md#updateComment) | **PUT** /comments/commentId | Update a comment


# **createComments**
> \OpenAPI\Client\Model\Comments createComments($comments_editable)

Save a new comment

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\CommentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$comments_editable = new \OpenAPI\Client\Model\CommentsEditable(); // \OpenAPI\Client\Model\CommentsEditable | 

try {
    $result = $apiInstance->createComments($comments_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CommentsApi->createComments: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **comments_editable** | [**\OpenAPI\Client\Model\CommentsEditable**](../Model/CommentsEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\Comments**](../Model/Comments.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **deleteComments**
> \OpenAPI\Client\Model\Comments deleteComments($comment_id)

Delete a comments

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\CommentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$comment_id = 'comment_id_example'; // string | ID of comments to return

try {
    $result = $apiInstance->deleteComments($comment_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CommentsApi->deleteComments: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **comment_id** | **string**| ID of comments to return |

### Return type

[**\OpenAPI\Client\Model\Comments**](../Model/Comments.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getCommentById**
> \OpenAPI\Client\Model\Comments getCommentById($comment_id)

Get single comment by ID

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\CommentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$comment_id = 'comment_id_example'; // string | ID of comments to return

try {
    $result = $apiInstance->getCommentById($comment_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CommentsApi->getCommentById: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **comment_id** | **string**| ID of comments to return |

### Return type

[**\OpenAPI\Client\Model\Comments**](../Model/Comments.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getComments**
> \OpenAPI\Client\Model\InlineResponse200 getComments($commentable_id, $commentable_type, $filter, $order_by, $order_direction, $per_page)

Returns all comments for a given type

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\CommentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$commentable_id = 56; // int | 
$commentable_type = 'commentable_type_example'; // string | 
$filter = 'filter_example'; // string | Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring.
$order_by = 'order_by_example'; // string | Field to order results by
$order_direction = 'asc'; // string | 
$per_page = 10; // int | 

try {
    $result = $apiInstance->getComments($commentable_id, $commentable_type, $filter, $order_by, $order_direction, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CommentsApi->getComments: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **commentable_id** | **int**|  | [optional]
 **commentable_type** | **string**|  | [optional]
 **filter** | **string**| Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring. | [optional]
 **order_by** | **string**| Field to order results by | [optional]
 **order_direction** | **string**|  | [optional] [default to &#39;asc&#39;]
 **per_page** | **int**|  | [optional] [default to 10]

### Return type

[**\OpenAPI\Client\Model\InlineResponse200**](../Model/InlineResponse200.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **updateComment**
> \OpenAPI\Client\Model\Comments updateComment($comment_id, $comments_editable)

Update a comment

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\CommentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$comment_id = 'comment_id_example'; // string | ID of comment to return
$comments_editable = new \OpenAPI\Client\Model\CommentsEditable(); // \OpenAPI\Client\Model\CommentsEditable | 

try {
    $result = $apiInstance->updateComment($comment_id, $comments_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CommentsApi->updateComment: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **comment_id** | **string**| ID of comment to return |
 **comments_editable** | [**\OpenAPI\Client\Model\CommentsEditable**](../Model/CommentsEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\Comments**](../Model/Comments.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


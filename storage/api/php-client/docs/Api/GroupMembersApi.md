# OpenAPI\Client\GroupMembersApi

All URIs are relative to *http://localhost/api/1.0*

Method | HTTP request | Description
------------- | ------------- | -------------
[**createGroupMembers**](GroupMembersApi.md#createGroupMembers) | **POST** /group_members | Save a new group_members
[**deleteGroupMembers**](GroupMembersApi.md#deleteGroupMembers) | **DELETE** /group_members/group_memberId | Delete a group_members
[**getGroupMemberById**](GroupMembersApi.md#getGroupMemberById) | **GET** /group_members/group_memberId | Get single group_member by ID
[**getGroupMembers**](GroupMembersApi.md#getGroupMembers) | **GET** /group_members | Returns all groups for a given member
[**getGroupMembersAvailable**](GroupMembersApi.md#getGroupMembersAvailable) | **GET** /group_members_available | Returns all groups available for a given member
[**getUserMembersAvailable**](GroupMembersApi.md#getUserMembersAvailable) | **GET** /user_members_available | Returns all users available for a given member


# **createGroupMembers**
> \OpenAPI\Client\Model\GroupMembers createGroupMembers($group_members_editable)

Save a new group_members

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\GroupMembersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$group_members_editable = new \OpenAPI\Client\Model\GroupMembersEditable(); // \OpenAPI\Client\Model\GroupMembersEditable | 

try {
    $result = $apiInstance->createGroupMembers($group_members_editable);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling GroupMembersApi->createGroupMembers: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **group_members_editable** | [**\OpenAPI\Client\Model\GroupMembersEditable**](../Model/GroupMembersEditable.md)|  |

### Return type

[**\OpenAPI\Client\Model\GroupMembers**](../Model/GroupMembers.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: application/json
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **deleteGroupMembers**
> \OpenAPI\Client\Model\GroupMembers deleteGroupMembers($group_member_id)

Delete a group_members

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\GroupMembersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$group_member_id = 'group_member_id_example'; // string | ID of group_members to return

try {
    $result = $apiInstance->deleteGroupMembers($group_member_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling GroupMembersApi->deleteGroupMembers: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **group_member_id** | **string**| ID of group_members to return |

### Return type

[**\OpenAPI\Client\Model\GroupMembers**](../Model/GroupMembers.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getGroupMemberById**
> \OpenAPI\Client\Model\GroupMembers getGroupMemberById($group_member_id)

Get single group_member by ID

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\GroupMembersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$group_member_id = 'group_member_id_example'; // string | ID of group_members to return

try {
    $result = $apiInstance->getGroupMemberById($group_member_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling GroupMembersApi->getGroupMemberById: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **group_member_id** | **string**| ID of group_members to return |

### Return type

[**\OpenAPI\Client\Model\GroupMembers**](../Model/GroupMembers.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getGroupMembers**
> \OpenAPI\Client\Model\InlineResponse2007 getGroupMembers($member_id, $order_by, $order_direction, $per_page)

Returns all groups for a given member

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\GroupMembersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$member_id = 56; // int | 
$order_by = 'order_by_example'; // string | Field to order results by
$order_direction = 'asc'; // string | 
$per_page = 10; // int | 

try {
    $result = $apiInstance->getGroupMembers($member_id, $order_by, $order_direction, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling GroupMembersApi->getGroupMembers: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **member_id** | **int**|  | [optional]
 **order_by** | **string**| Field to order results by | [optional]
 **order_direction** | **string**|  | [optional] [default to &#39;asc&#39;]
 **per_page** | **int**|  | [optional] [default to 10]

### Return type

[**\OpenAPI\Client\Model\InlineResponse2007**](../Model/InlineResponse2007.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getGroupMembersAvailable**
> \OpenAPI\Client\Model\InlineResponse2008 getGroupMembersAvailable($member_id, $member_type, $filter, $order_by, $order_direction, $per_page)

Returns all groups available for a given member

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\GroupMembersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$member_id = 'member_id_example'; // string | ID of group_members to return
$member_type = 'member_type_example'; // string | type of group_members to return
$filter = 'filter_example'; // string | Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring.
$order_by = 'order_by_example'; // string | Field to order results by
$order_direction = 'asc'; // string | 
$per_page = 10; // int | 

try {
    $result = $apiInstance->getGroupMembersAvailable($member_id, $member_type, $filter, $order_by, $order_direction, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling GroupMembersApi->getGroupMembersAvailable: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **member_id** | **string**| ID of group_members to return |
 **member_type** | **string**| type of group_members to return |
 **filter** | **string**| Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring. | [optional]
 **order_by** | **string**| Field to order results by | [optional]
 **order_direction** | **string**|  | [optional] [default to &#39;asc&#39;]
 **per_page** | **int**|  | [optional] [default to 10]

### Return type

[**\OpenAPI\Client\Model\InlineResponse2008**](../Model/InlineResponse2008.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getUserMembersAvailable**
> \OpenAPI\Client\Model\InlineResponse2009 getUserMembersAvailable($group_id, $filter, $order_by, $order_direction, $per_page)

Returns all users available for a given member

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure Bearer authorization: pm_api_bearer
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new OpenAPI\Client\Api\GroupMembersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$group_id = 'group_id_example'; // string | ID of group to return
$filter = 'filter_example'; // string | Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring.
$order_by = 'order_by_example'; // string | Field to order results by
$order_direction = 'asc'; // string | 
$per_page = 10; // int | 

try {
    $result = $apiInstance->getUserMembersAvailable($group_id, $filter, $order_by, $order_direction, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling GroupMembersApi->getUserMembersAvailable: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **group_id** | **string**| ID of group to return |
 **filter** | **string**| Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring. | [optional]
 **order_by** | **string**| Field to order results by | [optional]
 **order_direction** | **string**|  | [optional] [default to &#39;asc&#39;]
 **per_page** | **int**|  | [optional] [default to 10]

### Return type

[**\OpenAPI\Client\Model\InlineResponse2009**](../Model/InlineResponse2009.md)

### Authorization

[pm_api_bearer](../../README.md#pm_api_bearer)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)


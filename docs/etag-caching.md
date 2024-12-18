# ETag Documentation

## Introduction

### Description
An ETag (Entity Tag) is a unique fingerprint for a server response, similar to a hash based on the response content. If the content remains unchanged, the ETag stays the same.

- **How it works**:
  - When a browser requests content, it sends the previous ETag.
  - If the server’s ETag matches, it responds with a `304 Not Modified` status, signaling no changes.
  - This allows the browser to reuse its cached copy, saving time and bandwidth.

### Purpose of Implementing ETag
The goal of implementing ETag is to enhance application performance by avoiding reloading unchanged content. The browser stores a copy, and if confirmed valid by the server, no new download is required.

### Expected Benefits
- **Enhanced performance**: Reduces server load by avoiding processing unchanged content.
- **Bandwidth savings**: Cached content is reused.
- **Increased speed**: `304 Not Modified` responses are faster than resending content.
- **Resource optimization**: Lowers server CPU and memory consumption.

### Middleware Overview
The `HandleEtag` middleware manages ETags in HTTP responses to optimize requests through conditional responses (`304 Not Modified`), reducing server load and bandwidth usage.

## Implementation

### Middleware Setup
The middleware is applied globally to API `GET` routes and can be customized for specific routes via table configurations (`etag_tables`).

#### 1. HandleEtag Middleware
The `HandleEtag` middleware performs key tasks:
1. Applies ETag functionality to `GET` requests only.
2. Generates the ETag:
   - Based on related tables if configured (optional).
   - Based on the response content when no tables are specified (default).
3. Validates the client’s `If-None-Match` header. If it matches the calculated ETag, responds with a `304 Not Modified` status.
4. Ensures HTTP responses include the ETag header.

#### 2. ETag Generation
ETag generation is handled dynamically via the `EtagManager` class, using two methods:

**Based on Tables**
ETag is calculated using the `updated_at` timestamp of specified tables:
```php
public static function generateEtagFromTables(array $tables, string $source = 'updated_at'): string
{
    $lastUpdated = collect($tables)->map(function ($table) use ($source) {
        return DB::table($table)->max($source);
    })->max();

    return md5(auth()->id() . $lastUpdated);
}
```

**Based on Response**
ETag is generated from the response content:
```php
public static function getEtag(Request $request, Response $response): string
{
    return md5(auth()->id() . $response->getContent());
}
```

#### 3. Conditional Responses
The middleware handles conditional responses by checking the client’s `If-None-Match` header:
1. If the calculated ETag matches the client’s value, responds with `304 Not Modified`.
2. Otherwise, generates new content and includes a new ETag in the response.

#### 4. Middleware Configuration
The `HandleEtag` middleware is registered globally within the API group:
```php
// ProcessMaker/Providers/ProcessMakerServiceProvider.php
public function boot(): void
{
    Route::pushMiddlewareToGroup('api', HandleEtag::class);
}
```

Routes in `v1_1` can be mapped as follows:
```php
protected function mapApiRoutes()
{
    Route::middleware(['auth:api', 'etag'])
        ->group(base_path('routes/v1_1/api.php'));
}
```

#### 5. Route Usage
Routes can define custom settings, such as associating ETags with database tables:
```php
Route::prefix('api/1.0')->name('api.')->group(function () {
    Route::get('start_processes', [ProcessController::class, 'startProcesses'])
        ->middleware('etag') // Apply middleware
        ->defaults('etag_tables', 'processes') // Define related tables
        ->name('processes.start');
});
```

In this example:
- Middleware generates the ETag based on the last update of the `processes` table.
- If the client has the corresponding ETag, the server responds with `304 Not Modified` and does not execute the controller logic.

## Config file

The ETag functionality is managed through the `config/etag.php` file, which centralizes all related settings. This configuration file allows you to enable or disable ETag logging and caching, as well as customize key parameters such as history limits and cache expiration times.

### Key Options
- **`enabled`**: Determines whether the ETag functionality is active. If set to `false`, all ETag-related processing is skipped.
- **`log_dynamic_endpoints`**: Controls whether dynamic endpoints are logged. When disabled, no cache processing occurs.
- **`history_limit`**: Specifies the maximum number of ETags to track per endpoint.
- **`cache_expiration`**: Sets the duration (in minutes) for which the ETag history is cached.

## Logs

This middleware detects **highly dynamic endpoints** by tracking the history of ETags generated for each endpoint. It helps identify endpoints where ETags are consistently different, indicating dynamic responses that may require further optimization.

1. Tracks the last **N ETags** (default: 10) for each endpoint using Laravel's cache.
2. Logs endpoints as "highly dynamic" if all tracked ETags are unique.
3. Efficient caching and memory usage to minimize performance overhead.

### Example Logs

When an endpoint is identified as highly dynamic, the following log is generated:

```
ETag Dynamic endpoint detected:
{
  "url": "https://example.com/api/resource",
}
```

## Testing

### Unit Tests
Unit tests ensure proper functionality of the `HandleEtag` middleware and `EtagManager` class:
- **Middleware Tests**:
  - Ensures the `ETag` header is added correctly.
  - Validates `If-None-Match` requests with appropriate status codes (`200 OK` or `304 Not Modified`).
  - Handles weak ETags and generates custom ETags.
- **EtagManager Tests**:
  - Validates ETag generation using:
    - Default response content.
    - Custom callbacks (e.g., `md5`, `sha256`).
    - Dynamic data such as database tables.

### Manual Tests
Use tools like Postman or browser developer tools for manual testing:
1. **Verify ETag header generation**:
   - Make a `GET` request to an API route.
   - Check the response headers for a valid ETag value.
2. **Validate `If-None-Match` behavior**:
   - Send a `GET` request with the previous ETag.
   - Verify response status (`304 Not Modified` if unchanged, `200 OK` if changed).

### Example Scenarios
- Test routes using `etag_tables`:
  - Change data in related tables and observe changes in the ETag header.

## Conclusion

This implementation leverages ETags to optimize `GET` requests in the API, reducing bandwidth usage and improving performance. Middleware flexibility allows ETag customization based on specific tables or response content.

### Future Improvements
1. **ETag Versioning with Cache**:
   - Store ETag versions in cache to avoid database queries.
   - Enable manual invalidation via model events.
2. **Metrics Collection**:
   - Monitor request duration, `304` response percentage and bandwidth savings.

This solution is well-suited for global optimizations and specific dynamic routes.

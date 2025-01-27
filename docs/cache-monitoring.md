# ProcessMaker Cache Monitoring System

## Overview
The ProcessMaker Cache Monitoring System is a comprehensive solution for tracking and analyzing cache performance metrics in real-time. It uses Redis as a backend store for metrics and provides detailed insights into cache usage patterns, hit/miss rates, and memory consumption.

## Current Implementation

### Architecture
1. **Core Components**:
   - `CacheMetricsInterface`: Defines the contract for metrics collection
   - `PrometheusMetricsManager`: Implements metrics storage using Redis
   - `CacheMetricsDecorator`: Wraps cache implementations to collect metrics

2. **Key Features**:
   - Real-time metrics collection
   - Hit/miss rate tracking
   - Response time monitoring
   - Memory usage tracking
   - Top keys analysis
   - Performance insights

3. **Storage Strategy**:
   - Uses Redis hash structures for counters
   - Maintains rolling lists for timing data
   - Prefix-based key organization
   - Automatic cleanup of old data

### Metrics Collected
- Cache hits and misses
- Response times for hits/misses
- Memory usage per key
- Last write timestamps
- Access patterns
- Overall performance statistics


## Why Redis?
The current Redis-based implementation was chosen because:
1. **Performance**: Redis provides fast read/write operations
2. **Data Structures**: Native support for counters and lists
3. **TTL Support**: Automatic expiration of old metrics
4. **Scalability**: Easy to scale horizontally
5. **Integration**: Already used in ProcessMaker's infrastructure

## Usage Examples

### Basic Monitoring
```php
// Get hit rate for a specific key
$hitRate = $metrics->getHitRate('my_cache_key');

// Get memory usage
$usage = $metrics->getMemoryUsage('my_cache_key');

// Get performance summary
$summary = $metrics->getSummary();
```

### Performance Analysis
```php
// Get top accessed keys
$topKeys = $metrics->getTopKeys(10);

// Analyze response times
$avgHitTime = $metrics->getHitAvgTime('my_cache_key');
$avgMissTime = $metrics->getMissAvgTime('my_cache_key');
```

## Future Improvements
1. **Aggregation**: Add support for metric aggregation by time periods
2. **Sampling**: Implement sampling for high-traffic scenarios
3. **Alerts**: Add threshold-based alerting system
4. **Visualization**: Integrate with monitoring dashboards
5. **Custom Metrics**: Allow adding custom metrics per cache type

## Conclusion
The Redis-based monitoring system provides a good balance between performance, functionality, and maintainability. While there are alternatives available, the current implementation meets ProcessMaker's requirements for real-time cache monitoring with minimal overhead. 
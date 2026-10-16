<?php

return [
    // Enable Prometheus metric collection (counters, gauges, histograms). Default off.
    'enabled' => env('METRICS_ENABLED', false),

    // Interval in minutes for clearing metrics. Can be set via METRICS_CLEAR_INTERVAL env variable.
    'clear_interval' => env('METRICS_CLEAR_INTERVAL', 10),
];

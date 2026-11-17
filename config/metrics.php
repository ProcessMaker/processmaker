<?php

return [
    // Enable Prometheus metric collection (counters, gauges, histograms). Default off.
    'enabled' => env('METRICS_ENABLED', false),

    // Interval in minutes for clearing metrics. Default 24 hours (1440 minutes).
    'clear_interval' => env('METRICS_CLEAR_INTERVAL', 1440),

    // Max random delay (seconds) before scheduled clear (--stagger). Capped at clear_interval × 60 at runtime.
    'clear_stagger_max_seconds' => env('METRICS_CLEAR_STAGGER_MAX_SECONDS', 60),
];
